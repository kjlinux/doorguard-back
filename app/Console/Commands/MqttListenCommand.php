<?php

namespace App\Console\Commands;

use App\Events\SensorEventCreated;
use App\Models\Sensor;
use App\Models\SensorEvent;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttListenCommand extends Command
{
    protected $signature = 'mqtt:listen';

    protected $description = 'Ecoute les topics MQTT des capteurs et enregistre les événements de porte';

    public function handle(): int
    {
        $host = env('MQTT_HOST');
        $port = (int) env('MQTT_PORT', 8883);
        $username = env('MQTT_AUTH_USERNAME');
        $password = env('MQTT_AUTH_PASSWORD');
        $clientId = env('MQTT_CLIENT_ID', 'doorguard-api');
        $tlsEnabled = filter_var(env('MQTT_TLS_ENABLED', false), FILTER_VALIDATE_BOOLEAN);

        $this->info("Connexion au broker MQTT {$host}:{$port}...");
        $this->info("TLS: " . ($tlsEnabled ? 'oui' : 'non'));
        $this->info("Username: {$username}");
        $this->info("OpenSSL cafile: " . ini_get('openssl.cafile'));

        try {
            $mqtt = new MqttClient($host, $port, $clientId, MqttClient::MQTT_3_1_1);

            $connectionSettings = (new ConnectionSettings)
                ->setUsername($username)
                ->setPassword($password)
                ->setKeepAliveInterval(10)
                ->setConnectTimeout(30);

            if ($tlsEnabled) {
                $connectionSettings = $connectionSettings
                    ->setUseTls(true)
                    ->setTlsSelfSignedAllowed(true)
                    ->setTlsVerifyPeer(false)
                    ->setTlsVerifyPeerName(false);

                $caFile = env('MQTT_TLS_CA_FILE');
                if ($caFile) {
                    $connectionSettings = $connectionSettings->setTlsCertificateAuthorityFile($caFile);
                }
            }

            $this->info('Tentative de connexion MQTT...');
            $mqtt->connect($connectionSettings, true);
            $this->info('Connecté au broker MQTT.');

            // Souscrire au topic wildcard pour tous les capteurs
            $topic = 'doorguard/sensor/+/event';
            $mqtt->subscribe($topic, function (string $topic, string $message) {
                $this->processMessage($topic, $message);
            }, MqttClient::QOS_AT_LEAST_ONCE);

            $this->info("Souscrit au topic: {$topic}");
            $this->info('En attente de messages... (Ctrl+C pour arrêter)');

            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error('Erreur MQTT: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function processMessage(string $topic, string $message): void
    {
        $this->info("Message reçu sur [{$topic}]: {$message}");

        $data = json_decode($message, true);

        if (!$data) {
            $this->warn('Message invalide (JSON attendu), ignoré.');
            return;
        }

        // Extraire le sensor_id du topic: doorguard/sensor/{sensor_id}/event
        $parts = explode('/', $topic);
        $sensorIdentifier = $parts[2] ?? null;

        // Trouver le capteur par son mqtt_topic ou par l'identifiant dans le topic
        $sensor = Sensor::where('mqtt_topic', $topic)
            ->orWhere('id', $sensorIdentifier)
            ->first();

        if (!$sensor) {
            $this->warn("Capteur inconnu pour le topic [{$topic}], ignoré.");
            return;
        }

        // Mettre à jour le statut du capteur
        $sensor->update([
            'status' => 'online',
            'last_seen' => now(),
        ]);

        // Créer l'événement du capteur
        $sensorEvent = SensorEvent::create([
            'sensor_id' => $sensor->id,
            'status' => $data['action'] ?? $data['status'] ?? 'open',
            'detected_at' => !empty($data['timestamp']) ? $data['timestamp'] : now(),
        ]);

        // Broadcaster l'événement en temps réel
        event(new SensorEventCreated($sensorEvent));

        $this->info("Événement créé: capteur #{$sensor->id} ({$sensor->name}) - {$sensorEvent->status} à {$sensorEvent->detected_at}");
    }
}
