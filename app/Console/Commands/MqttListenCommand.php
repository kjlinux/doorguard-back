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
        $host = config('mqtt.host');
        $port = (int) config('mqtt.port', 8883);
        $username = config('mqtt.auth.username');
        $password = config('mqtt.auth.password');
        $clientId = config('mqtt.client_id', 'doorguard-api');
        $tlsEnabled = config('mqtt.tls_enabled', false);

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

                $caFile = config('mqtt.tls_ca_file');
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

        // Extraire le unique_id du topic: doorguard/sensor/{unique_id}/event
        $parts = explode('/', $topic);
        $uniqueId = $parts[2] ?? null;

        if (!$uniqueId) {
            $this->warn("Format de topic invalide [{$topic}], ignoré.");
            return;
        }

        // Trouver le capteur par son unique_id
        $sensor = Sensor::where('unique_id', $uniqueId)->first();

        if (!$sensor) {
            $this->warn("Capteur inconnu avec unique_id [{$uniqueId}] pour le topic [{$topic}], ignoré.");
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
