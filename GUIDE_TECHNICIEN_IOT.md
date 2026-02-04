# Guide Technicien IoT - Configuration Capteurs

## Configuration du capteur

1. Enregistrez le capteur depuis le frontend
2. Notez le topic MQTT affiché (exemple: `doorguard/sensor/sgci/event`)
3. Programmez ce topic dans le capteur IoT

## Format des messages

Quand le capteur accepte une carte, il doit publier sur son topic MQTT:

```json
{
  "action": "open",
  "timestamp": "2026-02-04T16:35:20Z"
}
```

## Paramètres MQTT

- Broker: Voir configuration serveur (HiveMQ)
- Port: 8883 (TLS) ou 1883
- QoS: 1 (at least once)

## Exemple de test avec HiveMQ CLI

```bash
mqtt pub -h your-hivemq-broker.com -p 8883 \
  -u username -pw password \
  -t "doorguard/sensor/sgci/event" \
  -m '{"action":"open","timestamp":"2026-02-04T16:35:20Z"}' \
  -q 1 -s
```

## Vérification

Le listener MQTT affichera:
```
Message reçu sur [doorguard/sensor/sgci/event]: {"action":"open","timestamp":"2026-02-04T16:35:20Z"}
Événement créé: capteur #X (sgci) - open à 2026-02-04 16:35:20
```
