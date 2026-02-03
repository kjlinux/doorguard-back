# Guide de Test MQTT - DoorGuard

Ce guide explique comment tester le flux complet : **Capteur → MQTT → Backend → Base de données → Frontend (temps réel)**

---

## 📋 Prérequis

### 1. Services requis

- ✅ PostgreSQL (Laragon)
- ✅ Backend Laravel
- ✅ Frontend React/Vue
- ✅ Broker MQTT HiveMQ Cloud (déjà configuré)

### 2. Outils de test MQTT

Choisissez UNE de ces options :

#### Option A : Mosquitto CLI (ligne de commande)
```bash
# Installation Windows (via winget)
winget install mosquitto

# Ou téléchargez depuis
https://mosquitto.org/download/
```

#### Option B : MQTT Explorer (interface graphique - RECOMMANDÉ)
```
Téléchargez depuis : http://mqtt-explorer.com/
Interface visuelle facile à utiliser
```

#### Option C : Python avec paho-mqtt
```bash
pip install paho-mqtt
```

---

## 🚀 Étapes de Test

### Étape 1 : Préparer la base de données

Ouvrez un terminal Laragon et exécutez :

```bash
cd c:\laragon\www\doorguard-back

# Exécuter les migrations
php artisan migrate

# Créer les données de test
php artisan db:seed --class=TestDataSeeder
```

**Résultat attendu :**
```
✅ Données de test créées avec succès!

Portes:
  - Entrée Principale (ID: 1)
  - Bureau 1 (ID: 2)
  - Parking (ID: 3)

Topics MQTT:
  - doorguard/sensor/1/event (Entrée Principale)
  - doorguard/sensor/2/event (Bureau 1)
  - doorguard/sensor/3/event (Parking)

Cartes autorisées:
  - ABC123456 (Jean Dupont)
  - DEF789012 (Marie Martin)
  - GHI345678 (Pierre Durand)
```

---

### Étape 2 : Démarrer les services

#### Terminal 1 : Laravel Reverb (WebSocket)
```bash
cd c:\laragon\www\doorguard-back
php artisan reverb:start
```

**Doit afficher :**
```
[2026-02-03 10:00:00] Server started on 127.0.0.1:8080
```

#### Terminal 2 : Backend Laravel (API)
```bash
cd c:\laragon\www\doorguard-back
php artisan serve
```

**Doit afficher :**
```
Laravel development server started: http://127.0.0.1:8000
```

#### Terminal 3 : MQTT Listener
```bash
cd c:\laragon\www\doorguard-back
php artisan mqtt:listen
```

**Doit afficher :**
```
Connexion au broker MQTT fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud:8883...
TLS: oui
Username: perseus911
Tentative de connexion MQTT...
Connecté au broker MQTT.
Souscrit au topic: doorguard/sensor/+/event
En attente de messages... (Ctrl+C pour arrêter)
```

#### Terminal 4 : Frontend
```bash
cd c:\laragon\www\doorguard-front
npm run dev
```

---

### Étape 3 : Envoyer un message de test

Choisissez votre méthode préférée :

#### Méthode A : Script Python (RECOMMANDÉ)

**Terminal 5 :**
```bash
cd c:\laragon\www\doorguard-back

# Installer la dépendance (une seule fois)
pip install paho-mqtt

# Test 1 : Carte autorisée sur capteur 1
python test-mqtt.py 1 ABC123456 open

# Test 2 : Carte autorisée sur capteur 2
python test-mqtt.py 2 DEF789012 open

# Test 3 : Carte inconnue sur capteur 1
python test-mqtt.py 1 UNKNOWN999 denied
```

#### Méthode B : PowerShell

```powershell
cd c:\laragon\www\doorguard-back

# Test avec carte autorisée
.\test-mqtt.ps1 -CardId "ABC123456" -SensorId "1" -Action "open"

# Test avec autre carte
.\test-mqtt.ps1 -CardId "DEF789012" -SensorId "2" -Action "open"
```

#### Méthode C : MQTT Explorer (GUI)

1. Ouvrez MQTT Explorer
2. Créez une nouvelle connexion :
   - **Host:** `fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud`
   - **Port:** `8883`
   - **Protocol:** `mqtts://`
   - **Username:** `perseus911`
   - **Password:** `Wemtinga2026@`
   - **Validate certificate:** Décoché
3. Connectez-vous
4. Publiez un message :
   - **Topic:** `doorguard/sensor/1/event`
   - **Message:**
   ```json
   {
     "card_id": "ABC123456",
     "action": "open",
     "timestamp": "2026-02-03T10:30:00Z"
   }
   ```

---

## ✅ Vérifications

### 1. Dans le Terminal 3 (MQTT Listener)

Vous devriez voir :

```
Message reçu sur [doorguard/sensor/1/event]: {"card_id":"ABC123456","action":"open","timestamp":"2026-02-03T10:30:00Z"}
Événement créé: porte #1 - open - carte: Jean Dupont
```

### 2. Dans la base de données

Ouvrez pgAdmin ou DBeaver et exécutez :

```sql
-- Voir les derniers événements
SELECT
    de.id,
    de.status,
    de.timestamp,
    d.name as door_name,
    ch.name as card_holder_name,
    ch.card_id
FROM door_events de
LEFT JOIN doors d ON de.door_id = d.id
LEFT JOIN card_holders ch ON de.card_holder_id = ch.id
ORDER BY de.timestamp DESC
LIMIT 10;
```

**Résultat attendu :**
```
id | status | timestamp           | door_name           | card_holder_name | card_id
---|--------|---------------------|---------------------|------------------|----------
1  | open   | 2026-02-03 10:30:00 | Entrée Principale   | Jean Dupont      | ABC123456
```

### 3. Dans le Frontend

Le frontend doit :
- Afficher une notification en temps réel
- Montrer l'événement dans la liste des accès
- Mettre à jour le statut de la porte

**Console du navigateur (F12) :**
```
[Reverb] Connected
[Event] door.event.created received: {...}
```

---

## 🧪 Scénarios de Test

### Test 1 : Carte autorisée
```bash
python test-mqtt.py 1 ABC123456 open
```
**Attendu :**
- ✅ Événement créé dans la base
- ✅ `card_holder_id` = 1 (Jean Dupont)
- ✅ Broadcast sur le channel `door-events`
- ✅ Affichage dans le frontend

### Test 2 : Carte non autorisée
```bash
python test-mqtt.py 1 INVALID_CARD denied
```
**Attendu :**
- ✅ Événement créé dans la base
- ✅ `card_holder_id` = NULL
- ✅ Broadcast sur le channel `door-events`
- ✅ Alerte dans le frontend

### Test 3 : Plusieurs capteurs
```bash
python test-mqtt.py 1 ABC123456 open
python test-mqtt.py 2 DEF789012 open
python test-mqtt.py 3 GHI345678 open
```
**Attendu :**
- ✅ 3 événements créés (3 portes différentes)
- ✅ Tous visibles dans le frontend

### Test 4 : Événements rapides (stress test)
```bash
# Windows PowerShell
for ($i=1; $i -le 10; $i++) {
    python test-mqtt.py 1 ABC123456 open
    Start-Sleep -Milliseconds 500
}
```
**Attendu :**
- ✅ 10 événements créés
- ✅ Tous affichés en temps réel

---

## 🔍 Dépannage

### Problème : "Connecté au broker MQTT" mais pas de messages reçus

**Causes possibles :**
1. Le topic dans le sensor ne correspond pas
2. Le sensor n'existe pas en base

**Solution :**
```sql
-- Vérifier les topics des sensors
SELECT id, name, mqtt_topic, door_id FROM sensors;

-- Mettre à jour si nécessaire
UPDATE sensors SET mqtt_topic = 'doorguard/sensor/1/event' WHERE id = 1;
```

### Problème : "Erreur MQTT: Connection refused"

**Causes possibles :**
1. Identifiants MQTT incorrects
2. Problème réseau/firewall

**Solution :**
```bash
# Tester la connexion manuellement
mosquitto_pub -h fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud \
              -p 8883 \
              -u perseus911 \
              -P "Wemtinga2026@" \
              -t "test" \
              -m "hello" \
              --insecure
```

### Problème : Événement créé mais pas de broadcast

**Causes possibles :**
1. Reverb n'est pas démarré
2. Queue worker non actif

**Solution :**
```bash
# Vérifier que Reverb tourne
php artisan reverb:start

# Vérifier les logs Laravel
tail -f storage/logs/laravel.log

# Tester l'event manuellement
php artisan tinker
>>> event(new App\Events\DoorEventCreated(App\Models\DoorEvent::first()));
```

### Problème : Frontend ne reçoit pas les événements

**Causes possibles :**
1. Laravel Echo mal configuré
2. Mauvaise URL de connexion

**Solution (Frontend) :**
```javascript
// Vérifier la configuration Echo
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'adwexqlaq3a9k65en5g8',
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// S'abonner au channel
window.Echo.channel('door-events')
    .listen('.door.event.created', (e) => {
        console.log('Event received:', e);
    });
```

---

## 📊 Surveillance en temps réel

### Surveiller les logs Laravel
```bash
tail -f storage/logs/laravel.log
```

### Surveiller les événements PostgreSQL
```sql
-- Créer une fonction pour surveiller les insertions
CREATE OR REPLACE FUNCTION notify_door_event()
RETURNS TRIGGER AS $$
BEGIN
    PERFORM pg_notify('door_events', json_build_object(
        'id', NEW.id,
        'door_id', NEW.door_id,
        'status', NEW.status,
        'timestamp', NEW.timestamp
    )::text);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Créer le trigger
DROP TRIGGER IF EXISTS door_event_notify ON door_events;
CREATE TRIGGER door_event_notify
    AFTER INSERT ON door_events
    FOR EACH ROW
    EXECUTE FUNCTION notify_door_event();
```

---

## 🎯 Résultat Final Attendu

Quand tout fonctionne correctement :

1. **Python envoie le message** → Console affiche "✅ Message publié"
2. **MQTT Listener reçoit** → Terminal 3 affiche "Message reçu sur..."
3. **Base de données** → Nouvelle ligne dans `door_events`
4. **Reverb broadcast** → Terminal 1 affiche "Broadcasting..."
5. **Frontend reçoit** → Notification + Mise à jour de la liste

**Temps total : < 1 seconde** 🚀

---

## 📞 Support

Si vous rencontrez des problèmes :
1. Vérifiez les logs : `storage/logs/laravel.log`
2. Vérifiez la connexion MQTT avec MQTT Explorer
3. Testez le broadcast manuellement avec `php artisan tinker`

---

**Dernière mise à jour :** 2026-02-03
