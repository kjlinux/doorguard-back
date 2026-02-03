#!/usr/bin/env python3
"""
Script Python pour tester l'envoi de messages MQTT vers DoorGuard
Installation: pip install paho-mqtt
"""

import json
import ssl
import sys
from datetime import datetime
import paho.mqtt.client as mqtt

# Configuration MQTT
MQTT_HOST = "fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud"
MQTT_PORT = 8883
MQTT_USERNAME = "perseus911"
MQTT_PASSWORD = "Wemtinga2026@"

def on_connect(client, userdata, flags, rc):
    if rc == 0:
        print("✅ Connecté au broker MQTT")
    else:
        print(f"❌ Erreur de connexion: code {rc}")
        sys.exit(1)

def on_publish(client, userdata, mid):
    print("✅ Message publié avec succès!")
    print("\nVérifiez:")
    print("  1. Les logs du listener MQTT (php artisan mqtt:listen)")
    print("  2. La base de données (table door_events)")
    print("  3. Le frontend (doit afficher l'événement en temps réel)")

def send_mqtt_message(sensor_id="1", card_id="ABC123456", action="open"):
    topic = f"doorguard/sensor/{sensor_id}/event"

    # Créer le message
    message = {
        "card_id": card_id,
        "action": action,
        "timestamp": datetime.utcnow().strftime("%Y-%m-%dT%H:%M:%SZ")
    }

    payload = json.dumps(message)

    print("=" * 50)
    print("Test MQTT - DoorGuard")
    print("=" * 50)
    print(f"\nHost:     {MQTT_HOST}")
    print(f"Port:     {MQTT_PORT} (TLS)")
    print(f"Topic:    {topic}")
    print(f"Message:  {payload}")
    print("\nConnexion au broker MQTT...\n")

    # Créer le client MQTT
    client = mqtt.Client(client_id="doorguard-test-python")
    client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)

    # Configuration TLS
    client.tls_set(cert_reqs=ssl.CERT_NONE)
    client.tls_insecure_set(True)

    # Callbacks
    client.on_connect = on_connect
    client.on_publish = on_publish

    try:
        # Connexion
        client.connect(MQTT_HOST, MQTT_PORT, 60)

        # Publier le message
        result = client.publish(topic, payload, qos=1)

        # Attendre la publication
        client.loop_start()
        result.wait_for_publish()
        client.loop_stop()

        client.disconnect()

    except Exception as e:
        print(f"\n❌ Erreur: {e}")
        sys.exit(1)

    print("\n" + "=" * 50)

if __name__ == "__main__":
    # Paramètres par défaut ou depuis la ligne de commande
    sensor_id = sys.argv[1] if len(sys.argv) > 1 else "1"
    card_id = sys.argv[2] if len(sys.argv) > 2 else "ABC123456"
    action = sys.argv[3] if len(sys.argv) > 3 else "open"

    send_mqtt_message(sensor_id, card_id, action)
