# Guide: Fix du problème unique_id pour les capteurs MQTT

## Problème identifié

Le capteur "sgci" était bien enregistré dans la base de données, mais les événements MQTT ne s'affichaient pas sur le dashboard car :

1. Le code MQTT cherchait les capteurs par `mqtt_topic` ou `id` (numérique)
2. L'identifiant extrait du topic ("sgci") est une chaîne, pas un ID numérique
3. Le capteur n'avait pas de colonne `unique_id` pour être trouvé facilement

## Solution implémentée

### Changements effectués

1. **Migration** : Ajout de la colonne `unique_id` à la table sensors
   - Fichier: `database/migrations/2026_02_04_000001_add_unique_id_to_sensors_table.php`
   - Peuple automatiquement le `unique_id` des capteurs existants depuis leur `mqtt_topic`

2. **Modèle Sensor** : Ajout de `unique_id` dans les fillables
   - Fichier: `app/Models/Sensor.php`

3. **SensorController** : Extraction et sauvegarde du `unique_id` depuis le topic MQTT
   - Fichier: `app/Http/Controllers/Api/SensorController.php`
   - Extrait "sgci" depuis "doorguard/sensor/sgci/event"

4. **MqttListenCommand** : Recherche des capteurs par `unique_id` au lieu de `id`
   - Fichier: `app/Console/Commands/MqttListenCommand.php`

## Installation

### Étape 1: Exécuter la migration

```bash
php artisan migrate
```

Ou utilisez le script fourni:

```bash
php update-sensors-unique-id.php
```

### Étape 2: Vérifier les capteurs existants

Vérifiez que le capteur "sgci" a bien son `unique_id`:

```bash
php artisan tinker
```

```php
$sensor = \App\Models\Sensor::where('name', 'sgci')->first();
echo "unique_id: " . $sensor->unique_id . "\n";
echo "mqtt_topic: " . $sensor->mqtt_topic . "\n";
```

Le résultat devrait être:
```
unique_id: sgci
mqtt_topic: doorguard/sensor/sgci/event
```

### Étape 3: Redémarrer le listener MQTT

Si le listener MQTT est en cours d'exécution, redémarrez-le:

```bash
# Arrêter le listener (Ctrl+C)
# Puis le redémarrer
php artisan mqtt:listen
```

### Étape 4: Tester avec MQTTX

Publiez un message de test sur le topic du capteur:

**Topic:** `doorguard/sensor/sgci/event`

**Message:**
```json
{
  "action": "open",
  "timestamp": "2026-02-04T10:30:00Z"
}
```

### Étape 5: Vérifier les événements

Dans le terminal du listener, vous devriez voir:
```
Message reçu sur [doorguard/sensor/sgci/event]: {"action":"open","timestamp":"2026-02-04T10:30:00Z"}
Événement créé: capteur #X (sgci) - open à 2026-02-04 10:30:00
```

Dans le dashboard frontend, l'événement devrait maintenant apparaître.

## Nouveaux capteurs

Pour les nouveaux capteurs créés via le frontend, le `unique_id` sera automatiquement extrait et sauvegardé. Plus besoin de configuration supplémentaire.

## Vérification des logs

Pour déboguer, ajoutez des logs dans le listener:

```bash
php artisan mqtt:listen > mqtt-debug.log 2>&1
```

Puis consultez le fichier `mqtt-debug.log` pour voir les messages.

## Rollback (en cas de problème)

Si vous rencontrez des problèmes, vous pouvez annuler la migration:

```bash
php artisan migrate:rollback
```

Cela supprimera la colonne `unique_id` de la table sensors.
