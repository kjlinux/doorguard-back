# DoorGuard - Guide MQTT pour l'ingenieur IoT

## Vue d'ensemble

DoorGuard est un systeme de controle d'acces par badge RFID. Un capteur ESP32 lit un badge, envoie l'UID au backend via MQTT, le backend verifie les permissions et repond avec un code hexadecimal que l'ESP32 interprete pour ouvrir ou refuser la porte.

```
+----------+       MQTT        +---------+      WebSocket     +----------+
|  ESP32   | <===============> | Backend | =================> | Frontend |
| (capteur)|                   | Laravel |                    | Next.js  |
+----------+                   +---------+                    +----------+
```

---

## 1. Architecture MQTT

### Broker

Le systeme utilise un broker MQTT externe (HiveMQ Cloud) avec TLS et authentification.

**Configuration (.env)** :

```env
MQTT_HOST=xxxx.s1.eu.hivemq.cloud
MQTT_PORT=8883
MQTT_CLIENT_ID=doorguard-api
MQTT_TLS_ENABLED=true
MQTT_AUTH_ENABLED=true
MQTT_AUTH_USERNAME=mon_user
MQTT_AUTH_PASSWORD=mon_password
```

### Structure des topics

Chaque capteur a un `unique_id` (identifiant unique stocke en base). Les topics suivent ce format :

| Topic | Direction | Description |
|-------|-----------|-------------|
| `doorguard/sensor/{unique_id}/event` | ESP32 → Backend | Le capteur publie un scan de badge |
| `doorguard/sensor/{unique_id}/response` | Backend → ESP32 | Le backend repond avec un code de commande |

Le backend s'abonne au topic wildcard `doorguard/sensor/+/event` pour ecouter tous les capteurs en une seule souscription.

---

## 2. Flux principal : Scan de badge

### Etape par etape

```
ESP32                          Backend                         Base de donnees
  |                               |                                |
  |  1. Badge scanne              |                                |
  |  Publie sur .../event         |                                |
  |  {"user_id":"A1B2C3",        |                                |
  |   "timestamp":1234567890,     |                                |
  |   "action":"scan"}            |                                |
  |------------------------------>|                                |
  |                               |  2. Identifie le capteur       |
  |                               |     via unique_id du topic     |
  |                               |-------------------------------->
  |                               |                                |
  |                               |  3. Met a jour le capteur      |
  |                               |     status=online              |
  |                               |     last_seen=now()            |
  |                               |-------------------------------->
  |                               |                                |
  |                               |  4. Cherche le badge           |
  |                               |     WHERE uid = "A1B2C3"       |
  |                               |-------------------------------->
  |                               |                                |
  |                               |  5. Cherche la porte           |
  |                               |     associee au capteur        |
  |                               |-------------------------------->
  |                               |                                |
  |                               |  6. Decide : ACCEPTED,         |
  |                               |     REFUSED ou REJECTED        |
  |                               |                                |
  |  7. Recoit le code            |                                |
  |  sur .../response             |                                |
  |<------------------------------|                                |
  |                               |                                |
  |  8. ESP32 actionne            |  9. Cree un AccessLog          |
  |     le relais ou refuse       |     + broadcast WebSocket      |
  |                               |-------------------------------->
```

### Message envoye par l'ESP32

L'ESP32 publie un JSON sur `doorguard/sensor/{unique_id}/event` :

```json
{
  "user_id": "A1B2C3D4",
  "timestamp": 1707400000,
  "action": "scan"
}
```

| Champ | Type | Description |
|-------|------|-------------|
| `user_id` | string | UID du badge RFID scanne (hex) |
| `timestamp` | integer | Timestamp UNIX du scan |
| `action` | string | Type d'action (toujours "scan" pour un badge) |

### Logique de decision du backend

Le backend evalue dans cet ordre :

```
Badge inconnu en base ?          → REJECTED  (badge pas du tout enregistre)
Badge desactive (is_active=0) ?  → REFUSED   (badge connu mais bloque)
Pas de porte liee au capteur ?   → REFUSED   (capteur pas configure)
Badge pas autorise sur la porte? → REFUSED   (pas de permission)
Tout OK                          → ACCEPTED  (acces accorde)
```

---

## 3. Codes de commande ESP32

Le backend repond avec un code hexadecimal brut (string) sur le topic `doorguard/sensor/{unique_id}/response`.

### Codes de reponse d'acces

| Code | Valeur | Description |
|------|--------|-------------|
| ACCEPTED | `0x001021J` | Acces autorise - l'ESP32 active le relais |
| REFUSED | `0x0030212` | Acces refuse - badge connu mais pas autorise |
| REJECTED | `0x1080814` | Badge inconnu - pas enregistre dans le systeme |

### Codes de commande a distance

Ces commandes sont envoyees depuis le frontend via l'API REST, puis le backend les publie sur MQTT :

| Code | Valeur | Description |
|------|--------|-------------|
| FORCED_OPEN | `0x004040J` | Force l'ouverture de la porte a distance |
| REBOOT | `0x108091S` | Redemarre l'ESP32 |
| RESET | `0x1080713` | Reset usine de l'ESP32 |
| SLEEP | `0x1080B17` | Met l'ESP32 en veille |
| WAKE_UP | `0x1080A1T` | Reveille l'ESP32 |
| STATUS | `0x1000119` | Demande l'etat actuel du capteur |

### Cote ESP32 : traitement du callback

L'ESP32 s'abonne a `doorguard/sensor/{unique_id}/response` et traite le code recu :

```cpp
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    String response = "";
    for (int i = 0; i < length; i++) {
        response += (char)payload[i];
    }

    if (response == ACCEPTED) {
        // Activer le relais (ouvrir la porte)
        activateRelay();
    }
    else if (response == REFUSED || response == REJECTED) {
        // Refuser l'acces (LED rouge, buzzer, etc.)
        denyAccess();
    }
    else if (response == FORCED_OPEN) {
        // Ouverture forcee a distance
        activateRelay();
    }
    else if (response == REBOOT) {
        ESP.restart();
    }
    else if (response == RESET) {
        // Effacer la config et redemarrer
        resetToFactory();
    }
    else if (response == SLEEP) {
        esp_deep_sleep_start();
    }
    else if (response == WAKE_UP) {
        // Reveil (si applicable)
    }
    else if (response == STATUS) {
        // Publier l'etat actuel sur le topic event
        publishStatus();
    }
}
```

---

## 4. Commandes a distance (API → MQTT)

Le frontend peut envoyer des commandes aux capteurs via l'API REST. Le backend se charge de publier le code sur MQTT.

### Endpoint

```
POST /api/mqtt/send-command
Authorization: Bearer {token}
Content-Type: application/json

{
    "sensor_id": 1,
    "command": "FORCED_OPEN"
}
```

### Commandes disponibles

`FORCED_OPEN`, `REBOOT`, `RESET`, `SLEEP`, `WAKE_UP`, `STATUS`

### Flux

```
Frontend                    Backend                    Broker MQTT              ESP32
   |                           |                           |                      |
   | POST /mqtt/send-command   |                           |                      |
   | { sensor_id, command }    |                           |                      |
   |-------------------------->|                           |                      |
   |                           | Cherche le capteur        |                      |
   |                           | Construit le topic        |                      |
   |                           | de reponse                |                      |
   |                           |                           |                      |
   |                           | Publie le code hex        |                      |
   |                           | sur .../response          |                      |
   |                           |-------------------------->|                      |
   |                           |                           |--------------------->|
   |                           |                           |                      |
   |                           | Si FORCED_OPEN :          |              Execute |
   |                           | cree un AccessLog         |              la cmd  |
   |                           | avec status=forced_open   |                      |
   |                           | badge_uid=REMOTE          |                      |
   |                           |                           |                      |
   | 200 OK                    |                           |                      |
   | { success, message }      |                           |                      |
   |<--------------------------|                           |                      |
```

### Construction du topic de reponse

Le backend derive le topic de reponse du `mqtt_topic` du capteur :

```
mqtt_topic du capteur:  doorguard/sensor/ABC123/event
topic de reponse:       doorguard/sensor/ABC123/response
```

Le remplacement est simple : `/event` → `/response`.

---

## 5. Listener MQTT (processus permanent)

### Demarrage

```bash
php artisan mqtt:listen
```

Ce processus tourne en permanence. Il doit etre lance au demarrage du serveur (via supervisor, systemd, ou Docker).

### Ce qu'il fait

1. Se connecte au broker MQTT (TLS + auth)
2. S'abonne a `doorguard/sensor/+/event` (wildcard `+` = n'importe quel unique_id)
3. Boucle indefiniment (`$mqtt->loop(true)`)
4. A chaque message recu :
   - Decode le JSON
   - Identifie le capteur via le `unique_id` extrait du topic
   - Met a jour le statut du capteur (`online`, `last_seen`)
   - Cherche le badge par `user_id` (= UID du badge)
   - Evalue la permission d'acces
   - Publie la reponse (code hex) sur le topic response du capteur
   - Cree un `AccessLog` en base
   - Broadcast un evenement WebSocket pour le frontend temps reel

### Supervision (production)

Exemple de config **Supervisor** :

```ini
[program:doorguard-mqtt]
command=php /chemin/vers/artisan mqtt:listen
directory=/chemin/vers/doorguard-back
autostart=true
autorestart=true
startsecs=5
stderr_logfile=/var/log/doorguard-mqtt.err.log
stdout_logfile=/var/log/doorguard-mqtt.out.log
user=www-data
```

---

## 6. Modele de donnees lie au MQTT

### Capteur (sensors)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | PK |
| name | string | Nom du capteur |
| location | string | Emplacement physique |
| unique_id | string | Identifiant unique (utilise dans les topics MQTT) |
| mqtt_topic | string | Topic complet ex: `doorguard/sensor/ABC123/event` |
| mqtt_broker | string (null) | Broker specifique (optionnel) |
| mqtt_port | int | Port (defaut 1883) |
| status | string | `online` ou `offline` |
| last_seen | timestamp (null) | Derniere activite |

### Porte (doors)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | PK |
| name | string | Nom de la porte |
| slug | string | Slug unique |
| location | string (null) | Emplacement |
| sensor_id | FK (null) | Capteur rattache |

Relation : une porte a un seul capteur (`sensor_id`), un capteur a une seule porte.

### Badge (badges)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | PK |
| uid | string | UID du badge RFID (unique) |
| holder_name | string | Nom du porteur |
| is_active | boolean | Badge actif/desactive |

### Permissions (badge_door)

Table pivot many-to-many entre badges et portes.

| Champ | Type | Description |
|-------|------|-------------|
| badge_id | FK | Badge autorise |
| door_id | FK | Porte autorisee |

### Logs d'acces (access_logs)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | PK |
| badge_id | FK (null) | Badge utilise (null si inconnu) |
| door_id | FK (null) | Porte concernee |
| sensor_id | FK (null) | Capteur qui a detecte |
| status | string | `accepted`, `refused`, `rejected`, `forced_open` |
| badge_uid | string | UID du badge (stocke meme si badge inconnu) |
| responded_at | timestamp | Moment de la reponse |

---

## 7. Temps reel (WebSocket)

Quand un access log est cree (scan de badge ou commande FORCED_OPEN), un evenement est broadcast sur le canal WebSocket `access-logs` :

- Nom de l'evenement : `access.log.created`
- Canal : `access-logs` (public)
- Donnees : toutes les infos de l'access log (badge, porte, capteur, statut)

Le frontend ecoute ce canal pour afficher les notifications et mettre a jour le tableau de bord en temps reel.

---

## 8. Configuration ESP32

### Ce que l'ESP32 doit savoir

| Parametre | Valeur |
|-----------|--------|
| Broker MQTT | `xxxx.s1.eu.hivemq.cloud` (ou votre broker) |
| Port | `8883` (TLS) |
| Username | Le meme que dans le .env du backend |
| Password | Le meme que dans le .env du backend |
| TLS | Active |
| Topic publish | `doorguard/sensor/{UNIQUE_ID}/event` |
| Topic subscribe | `doorguard/sensor/{UNIQUE_ID}/response` |
| Client ID | Unique par capteur, ex: `doorguard-esp32-ABC123` |
| QoS | 1 (at least once) recommande |

### UNIQUE_ID

Le `unique_id` est l'identifiant du capteur. Il doit etre :
- Le meme dans le firmware ESP32 et dans la base de donnees (champ `unique_id` de la table `sensors`)
- Unique par capteur
- Utilise dans le `mqtt_topic` du capteur : `doorguard/sensor/{unique_id}/event`

### Enregistrement d'un capteur

1. Sur le frontend, aller dans **Gestion des capteurs**
2. Renseigner le nom, l'emplacement et le topic MQTT (`doorguard/sensor/{unique_id}/event`)
3. Le capteur apparait dans la liste avec le statut **Hors ligne**
4. Des que l'ESP32 publie un message, le backend passe le capteur en **En ligne**

### Rattachement capteur/porte

1. Sur le frontend, aller dans **Gestion des portes**
2. Selectionner le capteur dans la liste deroulante (seuls les capteurs non rattaches sont affiches)
3. Le nom et l'emplacement sont remplis automatiquement depuis le capteur
4. On peut detacher/reassigner un capteur a tout moment

---

## 9. Debugger le MQTT

### Verifier la connexion

```bash
# Tester la connexion depuis le backend
php artisan tinker
>>> $mqtt = new \PhpMqtt\Client\MqttClient(config('mqtt.host'), config('mqtt.port'), 'test', \PhpMqtt\Client\MqttClient::MQTT_3_1_1);
>>> $settings = (new \PhpMqtt\Client\ConnectionSettings)->setUsername(config('mqtt.auth.username'))->setPassword(config('mqtt.auth.password'))->setUseTls(true)->setTlsSelfSignedAllowed(true)->setTlsVerifyPeer(false)->setTlsVerifyPeerName(false);
>>> $mqtt->connect($settings);
>>> echo "OK";
```

### Tester un publish

```bash
# Via l'API (authentifie)
curl -X POST http://localhost:8000/api/mqtt/test \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"topic": "doorguard/sensor/TEST/event"}'
```

### Simuler un scan de badge

Publier manuellement sur le broker (avec un outil comme MQTTX, mosquitto_pub, etc.) :

```
Topic:   doorguard/sensor/ABC123/event
Payload: {"user_id":"A1B2C3D4","timestamp":1707400000,"action":"scan"}
```

Le listener (`php artisan mqtt:listen`) doit afficher la decision dans la console.

### Erreurs courantes

| Erreur | Cause | Solution |
|--------|-------|----------|
| `unauthorized` | Mauvais username/password MQTT | Verifier .env, faire `php artisan config:clear` |
| `Connection timed out` | Broker injoignable | Verifier host/port, firewall, TLS |
| Capteur inconnu | Le `unique_id` du topic ne correspond a aucun capteur en base | Verifier le `mqtt_topic` enregistre |
| Pas de reponse | Le listener n'est pas lance | Lancer `php artisan mqtt:listen` |
| Badge rejected alors qu'il est enregistre | Le `uid` envoye ne correspond pas exactement au `uid` en base | Verifier la casse et le format du UID |

---

## 10. Resume rapide

```
ESP32 scanne un badge
    → publie {"user_id":"..."} sur doorguard/sensor/{id}/event

Backend recoit le message (mqtt:listen)
    → identifie capteur via unique_id
    → cherche badge par uid
    → verifie permission badge ↔ porte
    → repond avec code hex sur doorguard/sensor/{id}/response
    → cree un AccessLog + broadcast WebSocket

ESP32 recoit le code
    → 0x001021J = ouvrir la porte
    → 0x0030212 = refuser (badge connu, pas autorise)
    → 0x1080814 = rejeter (badge inconnu)

Frontend peut aussi envoyer des commandes a distance
    → POST /api/mqtt/send-command {sensor_id, command}
    → FORCED_OPEN, REBOOT, RESET, SLEEP, WAKE_UP, STATUS
```
