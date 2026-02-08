# Plan de refonte DoorGuard : Passage au contrôle d'accès par badge RFID

## Résumé du changement

**AVANT** : Le capteur ESP32 envoie un événement "porte ouverte/fermée" → le backend l'enregistre → le frontend affiche.

**APRÈS** : Le capteur ESP32 scanne un badge RFID → publie l'UID via MQTT → le backend vérifie les droits d'accès (badge + porte) → le backend répond via MQTT avec un code de commande (ACCEPTED, REFUSED, etc.) → l'ESP32 ouvre ou refuse la porte → le frontend gère tout le CRUD (badges, portes, permissions, logs d'accès) + commandes à distance.

---

## PARTIE 1 — BACKEND (Laravel)

### 1.1 Nouvelles migrations

**a) `badges` table** (remplace card_holders)
```
- id
- uid (string, unique) — UID RFID du badge
- holder_name (string) — Nom du porteur
- is_active (boolean, default true)
- timestamps
```

**b) `doors` table** (enrichir l'existante)
```
- id
- name
- slug (unique)
- location
- sensor_id (FK nullable → sensors) — capteur associé
- timestamps
```

**c) `badge_door` table pivot** (permissions badge↔porte)
```
- id
- badge_id (FK → badges)
- door_id (FK → doors)
- timestamps
```

**d) `access_logs` table** (remplace sensor_events + door_events)
```
- id
- badge_id (FK nullable → badges)
- door_id (FK nullable → doors)
- sensor_id (FK nullable → sensors)
- status (enum: accepted, refused, rejected, forced_open)
- badge_uid (string) — l'UID brut scanné (même si badge inconnu)
- responded_at (timestamp)
- timestamps
```

### 1.2 Modèles Eloquent

**Badge** — `fillable: uid, holder_name, is_active` + relations: `doors()` (belongsToMany), `accessLogs()` (hasMany)

**Door** — enrichir : ajouter `sensor()` (belongsTo Sensor), `badges()` (belongsToMany Badge), `accessLogs()` (hasMany)

**AccessLog** — `fillable: badge_id, door_id, sensor_id, status, badge_uid, responded_at` + relations vers Badge, Door, Sensor

**Sensor** — ajouter `door()` (belongsTo ou hasOne Door)

### 1.3 Nouveau flux MQTT (`MqttListenCommand`)

Le `mqtt:listen` reçoit sur `doorguard/sensor/{unique_id}/event` un JSON :
```json
{ "user_id": "AB:CD:EF:12", "timestamp": "2026-...", "action": "badge_scan" }
```

**Logique :**
1. Extraire `unique_id` du topic → trouver le `Sensor`
2. Trouver la `Door` associée au sensor
3. Chercher le `Badge` par `uid = user_id`
4. Vérifier :
   - Badge existe ET `is_active` = true ?
   - Badge a la permission sur cette porte (table pivot `badge_door`) ?
5. Déterminer le code de réponse :
   - Badge inconnu → `REJECTED` (0x1080814)
   - Badge inactif → `REFUSED` (0x0030212)
   - Badge sans permission sur cette porte → `REFUSED` (0x0030212)
   - Badge valide + permission → `ACCEPTED` (0x001021J)
6. Publier la réponse sur le topic de réponse du sensor : `doorguard/sensor/{unique_id}/response`
7. Créer un `AccessLog` (badge_id, door_id, sensor_id, status, badge_uid, responded_at)
8. Broadcaster l'événement en temps réel via WebSocket

### 1.4 Nouveaux contrôleurs API

**BadgeController** (CRUD complet)
- `GET /api/badges` — liste tous les badges
- `POST /api/badges` — créer un badge (uid, holder_name)
- `GET /api/badges/{badge}` — détail
- `PUT /api/badges/{badge}` — modifier
- `DELETE /api/badges/{badge}` — supprimer
- `POST /api/badges/{badge}/toggle` — activer/désactiver

**DoorController** (enrichir/réactiver)
- `GET /api/doors` — liste des portes avec sensor/badges
- `POST /api/doors` — créer une porte
- `GET /api/doors/{door}` — détail avec permissions
- `PUT /api/doors/{door}` — modifier
- `DELETE /api/doors/{door}` — supprimer
- `POST /api/doors/{door}/badges` — assigner des badges
- `DELETE /api/doors/{door}/badges/{badge}` — retirer un badge

**AccessLogController** (remplace SensorEventController)
- `GET /api/access-logs` — liste paginée avec filtres (door_id, badge_id, status, date)
- `GET /api/access-logs/{id}` — détail

**DashboardController** (adapter)
- `GET /api/dashboard/metrics` — total accès 24h, accès refusés, portes actives, capteurs en ligne
- `GET /api/dashboard/hourly-activity` — accès par heure
- `GET /api/dashboard/door-activity` — accès par porte

**MqttController** (enrichir)
- `POST /api/mqtt/send-command` — envoyer une commande (FORCED_OPEN, REBOOT, STATUS, RESET, SLEEP, WAKE_UP) à un capteur via MQTT

### 1.5 Resources API

**BadgeResource** : id, uid, holderName, isActive, doorsCount, createdAt
**DoorResource** : id, name, slug, location, sensor (embedded), badgesCount
**AccessLogResource** : id, badgeUid, holderName, doorName, doorLocation, sensorName, status, respondedAt

### 1.6 Events Broadcasting

**AccessLogCreated** (remplace SensorEventCreated) — broadcast sur channel `access-logs`

### 1.7 Routes API finales

```php
// Public
POST /api/login

// Protected (auth:sanctum)
POST /api/logout
GET  /api/me

// Dashboard
GET /api/dashboard/metrics
GET /api/dashboard/hourly-activity
GET /api/dashboard/door-activity

// Badges CRUD
GET    /api/badges
POST   /api/badges
GET    /api/badges/{badge}
PUT    /api/badges/{badge}
DELETE /api/badges/{badge}
POST   /api/badges/{badge}/toggle

// Doors CRUD
GET    /api/doors
POST   /api/doors
GET    /api/doors/{door}
PUT    /api/doors/{door}
DELETE /api/doors/{door}
POST   /api/doors/{door}/badges
DELETE /api/doors/{door}/badges/{badge}

// Access Logs
GET /api/access-logs
GET /api/access-logs/{accessLog}

// Sensors (garder existant)
apiResource /api/sensors

// MQTT Commands
POST /api/mqtt/test
POST /api/mqtt/send-command
```

---

## PARTIE 2 — FRONTEND (Next.js)

### 2.1 Nouvelles pages

**`/badges`** — Page de gestion des badges RFID
- Liste des badges avec statut (actif/inactif), nom du porteur, UID, nombre de portes autorisées
- Formulaire d'ajout de badge
- Actions : modifier, supprimer, toggle actif/inactif
- Dialog d'assignation des portes par badge

**`/doors`** — Page de gestion des portes
- Liste des portes avec capteur associé, localisation, nombre de badges autorisés
- Formulaire d'ajout/modification de porte
- Gestion des permissions (assigner/retirer des badges)
- Boutons de commande à distance : Ouvrir (FORCED_OPEN), Reboot, Status

**`/access-logs`** — Page des logs d'accès (remplace la vue événements)
- Table paginée avec filtres (par porte, par badge, par statut, par date)
- Statut avec couleurs : vert (accepted), rouge (refused/rejected), orange (forced_open)
- Temps réel via WebSocket

**`/dashboard`** — Adapter
- Métriques : Total accès 24h, Accès refusés, Portes actives, Capteurs en ligne
- Graphique activité horaire (accès)
- Graphique activité par porte
- Table derniers accès en temps réel

### 2.2 Navigation (Header)

Ajouter dans le header : Dashboard | Portes | Badges | Capteurs | Logs d'accès

### 2.3 Nouveaux types TypeScript

```ts
interface Badge {
  id: string; uid: string; holderName: string; isActive: boolean; doorsCount: number; createdAt: Date
}
interface Door {
  id: string; name: string; slug: string; location: string; sensor?: Sensor; badgesCount: number
}
interface AccessLog {
  id: string; badgeUid: string; holderName: string; doorName: string; doorLocation: string
  sensorName: string; status: "accepted" | "refused" | "rejected" | "forced_open"; respondedAt: Date
}
```

### 2.4 Nouvelles fonctions API (lib/api.ts)

- `getBadges()`, `createBadge()`, `updateBadge()`, `deleteBadge()`, `toggleBadge()`
- `getDoors()`, `createDoor()`, `updateDoor()`, `deleteDoor()`, `assignBadgesToDoor()`, `removeBadgeFromDoor()`
- `getAccessLogs()`, `getAccessLog()`
- `sendMqttCommand()` — pour FORCED_OPEN, REBOOT, STATUS, etc.
- Adapter `getMetrics()` aux nouvelles métriques

### 2.5 WebSocket

Adapter `use-sensor-events.ts` → `use-access-logs.ts` pour écouter le channel `access-logs` au lieu de `sensor-events`.

---

## PARTIE 3 — Ordre d'exécution

1. Migrations backend (badges, badge_door, access_logs, modifier doors+sensors)
2. Modèles Eloquent (Badge, AccessLog, modifier Door, Sensor)
3. Resources API (BadgeResource, DoorResource, AccessLogResource)
4. Events (AccessLogCreated)
5. Requests de validation (StoreBadgeRequest, UpdateBadgeRequest, StoreDoorRequest, etc.)
6. Contrôleurs (BadgeController, DoorController enrichi, AccessLogController, DashboardController adapté, MqttController enrichi)
7. Refonte MqttListenCommand (logique de décision badge)
8. Routes API
9. Frontend : types, api.ts, pages, composants, navigation
