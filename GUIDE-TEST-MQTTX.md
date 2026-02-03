# Guide de Test avec MQTTX - DoorGuard

MQTTX est un client MQTT graphique moderne et facile à utiliser.

---

## 📥 Installation

Téléchargez MQTTX depuis : **https://mqttx.app/**

Ou installez via :
```bash
winget install EMQX.MQTTX
```

---

## 🔧 Configuration de la connexion

### Étape 1 : Ouvrir MQTTX

1. Lancez MQTTX
2. Cliquez sur **"+ New Connection"** ou **"Nouvelle connexion"**

### Étape 2 : Configurer la connexion HiveMQ

Remplissez les champs suivants :

#### Informations générales
- **Name** (Nom) : `DoorGuard HiveMQ`
- **Client ID** : `mqttx-doorguard-test` (ou laissez auto-généré)

#### Connexion
- **Host** : `mqtts://fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud`
- **Port** : `8883`
- **Username** : `perseus911`
- **Password** : `Wemtinga2026@`

#### SSL/TLS
- **SSL/TLS** : ✅ Activé
- **Certificate** : `Self signed` (ou `None`)
- **Strict validate Certificate** : ❌ Désactivé

#### Options avancées (facultatif)
- **Keep Alive** : `60`
- **Clean Session** : ✅ Activé
- **MQTT Version** : `5.0` ou `3.1.1`

### Étape 3 : Tester la connexion

Cliquez sur **"Connect"** en haut à droite.

**✅ Succès** : Le statut passe à "Connected" (vert)
**❌ Échec** : Vérifiez les identifiants et le SSL/TLS

---

## 📡 S'abonner au topic (pour voir les messages)

### Étape 1 : Ajouter une souscription

1. Dans la fenêtre de connexion active
2. Section **"Subscriptions"** en bas
3. Cliquez sur **"+ New Subscription"**

### Étape 2 : Configurer la souscription

- **Topic** : `doorguard/sensor/+/event`
- **QoS** : `1`
- **Color** : Choisissez une couleur (ex: bleu)

Cliquez sur **"Confirm"**

> Le `+` est un wildcard qui écoute tous les sensors (1, 2, 3, etc.)

---

## 📤 Publier un message de test

### Étape 1 : Section Publish

Dans la partie droite de l'interface :

1. **Topic** : `doorguard/sensor/1/event`
2. **QoS** : `1`
3. **Payload** : Sélectionnez **"JSON"**

### Étape 2 : Messages JSON à tester

#### Test 1 : Carte autorisée (Jean Dupont)
```json
{
  "card_id": "ABC123456",
  "action": "open",
  "timestamp": "2026-02-03T10:30:00Z"
}
```

#### Test 2 : Carte autorisée (Marie Martin)
```json
{
  "card_id": "DEF789012",
  "action": "open",
  "timestamp": "2026-02-03T10:35:00Z"
}
```

#### Test 3 : Carte autorisée (Pierre Durand)
```json
{
  "card_id": "GHI345678",
  "action": "open",
  "timestamp": "2026-02-03T10:40:00Z"
}
```

#### Test 4 : Carte non autorisée
```json
{
  "card_id": "UNKNOWN999",
  "action": "denied",
  "timestamp": "2026-02-03T10:45:00Z"
}
```

#### Test 5 : Porte du bureau
```json
{
  "card_id": "DEF789012",
  "action": "open",
  "timestamp": "2026-02-03T11:00:00Z"
}
```
**Topic** : `doorguard/sensor/2/event`

#### Test 6 : Porte du parking
```json
{
  "card_id": "GHI345678",
  "action": "open",
  "timestamp": "2026-02-03T11:10:00Z"
}
```
**Topic** : `doorguard/sensor/3/event`

### Étape 3 : Envoyer

Cliquez sur le bouton **"Publish"** (ou icône d'envoi ➤)

---

## 👀 Vérifier les résultats

### Dans MQTTX

Si vous êtes souscrit au topic `doorguard/sensor/+/event`, vous verrez vos propres messages apparaître dans la section **"Messages"** :

```
doorguard/sensor/1/event
{
  "card_id": "ABC123456",
  "action": "open",
  "timestamp": "2026-02-03T10:30:00Z"
}
```

### Dans le terminal MQTT Listener

```bash
Message reçu sur [doorguard/sensor/1/event]: {"card_id":"ABC123456","action":"open","timestamp":"2026-02-03T10:30:00Z"}
Événement créé: porte #1 - open - carte: Jean Dupont
```

### Dans la base de données

```sql
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
LIMIT 5;
```

### Dans le frontend

- Notification en temps réel
- Nouvel événement dans la liste
- Statut de la porte mis à jour

---

## 🎯 Scénarios de test complets

### Scénario 1 : Accès normal d'un employé

1. **Publier sur** `doorguard/sensor/1/event` :
```json
{
  "card_id": "ABC123456",
  "action": "open",
  "timestamp": "2026-02-03T08:30:00Z"
}
```

**Résultat attendu :**
- ✅ Événement créé avec `card_holder_id = 1`
- ✅ Status = "open"
- ✅ Capteur #1 status = "online"
- ✅ Frontend affiche "Jean Dupont a ouvert Entrée Principale"

---

### Scénario 2 : Tentative d'accès non autorisé

1. **Publier sur** `doorguard/sensor/1/event` :
```json
{
  "card_id": "HACKER_CARD",
  "action": "denied",
  "timestamp": "2026-02-03T08:35:00Z"
}
```

**Résultat attendu :**
- ✅ Événement créé avec `card_holder_id = NULL`
- ✅ Status = "denied"
- ✅ Frontend affiche une alerte rouge
- ✅ Possibilité de déclencher une notification de sécurité

---

### Scénario 3 : Employé accède à plusieurs portes

**Message 1** - `doorguard/sensor/1/event` :
```json
{
  "card_id": "DEF789012",
  "action": "open",
  "timestamp": "2026-02-03T09:00:00Z"
}
```

**Message 2** - `doorguard/sensor/2/event` (30 sec après) :
```json
{
  "card_id": "DEF789012",
  "action": "open",
  "timestamp": "2026-02-03T09:00:30Z"
}
```

**Message 3** - `doorguard/sensor/3/event` (1 min après) :
```json
{
  "card_id": "DEF789012",
  "action": "open",
  "timestamp": "2026-02-03T09:01:00Z"
}
```

**Résultat attendu :**
- ✅ 3 événements créés pour Marie Martin
- ✅ Traçabilité complète de son parcours
- ✅ Timeline visible dans le frontend

---

### Scénario 4 : Stress test (envois rapides)

Envoyez 10 messages rapidement (cliquez sur Publish 10 fois de suite) :

```json
{
  "card_id": "ABC123456",
  "action": "open",
  "timestamp": "2026-02-03T10:00:00Z"
}
```

**Résultat attendu :**
- ✅ Tous les messages sont traités
- ✅ Pas de perte de messages
- ✅ Tous affichés dans le frontend

---

## 🔍 Debugging avec MQTTX

### Voir les logs de connexion

MQTTX affiche les logs en bas de l'interface :
- **Connected** : Connexion réussie
- **Subscribed to topic** : Souscription réussie
- **Message published** : Message envoyé

### Tester la connectivité

1. Souscrivez au topic `$SYS/#` pour voir les statistiques du broker
2. Publiez sur un topic test : `test/hello`
3. Si vous recevez votre propre message, la connexion fonctionne

### Simuler un capteur réel

Pour simuler un capteur qui envoie des messages périodiquement :

1. Utilisez la fonction **"Scripts"** de MQTTX
2. Ou cliquez rapidement sur Publish plusieurs fois

---

## 📊 Visualisation dans MQTTX

MQTTX offre plusieurs vues :

### Vue Messages
Affiche tous les messages reçus en temps réel avec :
- Topic
- Payload (JSON formaté)
- QoS
- Timestamp
- Couleur personnalisée

### Vue Analytics (si disponible)
- Nombre de messages reçus/envoyés
- Graphiques en temps réel
- Performance

---

## ⚙️ Configuration avancée

### Retained Messages

Si vous voulez que le dernier message soit conservé :
- **Retain** : ✅ Activé

Utile pour connaître le dernier état d'un capteur.

### Last Will

Pour simuler une déconnexion du capteur :
- **Last Will Topic** : `doorguard/sensor/1/status`
- **Last Will Message** : `{"status": "offline"}`
- **Last Will QoS** : `1`
- **Last Will Retain** : ✅

---

## 🎬 Flux complet de test

### Avant de commencer

1. ✅ Migrations exécutées (`php artisan migrate`)
2. ✅ Données de test créées (`php artisan db:seed --class=TestDataSeeder`)
3. ✅ Laravel Reverb démarré (`php artisan reverb:start`)
4. ✅ Backend démarré (`php artisan serve`)
5. ✅ MQTT Listener démarré (`php artisan mqtt:listen`)
6. ✅ Frontend démarré (`npm run dev`)
7. ✅ MQTTX connecté au broker

### Test complet

1. **Dans MQTTX** : Publier un message sur `doorguard/sensor/1/event`
2. **Terminal MQTT Listener** : Vérifier la réception et le traitement
3. **Base de données** : Vérifier l'insertion dans `door_events`
4. **Terminal Reverb** : Vérifier le broadcast
5. **Frontend** : Vérifier l'affichage en temps réel

**Temps total : < 1 seconde** ⚡

---

## 🆘 Dépannage

### "Connection failed"

- ✅ Vérifiez l'URL : `mqtts://` (avec le 's')
- ✅ Port : `8883`
- ✅ SSL/TLS activé
- ✅ Certificate : Self signed

### "Message published but not received by listener"

- ✅ Vérifiez le topic exact : `doorguard/sensor/1/event`
- ✅ Vérifiez que le listener est bien démarré
- ✅ Vérifiez les logs Laravel

### "Event created but not on frontend"

- ✅ Reverb est-il démarré ?
- ✅ Le frontend est-il connecté au WebSocket ?
- ✅ Ouvrez la console du navigateur (F12)

---

## 📸 Captures d'écran de configuration

### Configuration de connexion
```
Name: DoorGuard HiveMQ
Host: mqtts://fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud
Port: 8883
Username: perseus911
Password: Wemtinga2026@
SSL/TLS: ✅
```

### Topic à publier
```
Topic: doorguard/sensor/1/event
QoS: 1
Payload: JSON
```

### Topic de souscription
```
Topic: doorguard/sensor/+/event
QoS: 1
```

---

## 🎯 Commandes rapides de test

Copiez-collez ces JSON directement dans MQTTX :

### Copie rapide #1
```json
{"card_id":"ABC123456","action":"open","timestamp":"2026-02-03T10:30:00Z"}
```

### Copie rapide #2
```json
{"card_id":"DEF789012","action":"open","timestamp":"2026-02-03T10:35:00Z"}
```

### Copie rapide #3
```json
{"card_id":"UNKNOWN999","action":"denied","timestamp":"2026-02-03T10:40:00Z"}
```

---

**Dernière mise à jour :** 2026-02-03

**Liens utiles :**
- MQTTX : https://mqttx.app/
- HiveMQ Cloud : https://www.hivemq.com/mqtt-cloud-broker/
- Documentation MQTT : https://mqtt.org/
