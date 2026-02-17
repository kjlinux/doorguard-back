<?php

namespace App\Console\Commands;

use App\Events\AccessLogCreated;
use App\Events\SensorStatusUpdated;
use App\Models\AccessLog;
use App\Models\Badge;
use App\Models\Sensor;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttListenCommand extends Command
{
    protected $signature = 'mqtt:listen';

    protected $description = 'Ecoute les topics MQTT des capteurs et traite les demandes d\'accès par badge RFID';

    // Codes de commande ESP32
    private const ACCEPTED = '0x001021J';
    private const REFUSED = '0x0030212';
    private const REJECTED = '0x1080814';

    private ?MqttClient $mqtt = null;

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

        try {
            $this->mqtt = new MqttClient($host, $port, $clientId, MqttClient::MQTT_3_1_1);

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
            $this->mqtt->connect($connectionSettings, true);
            $this->info('Connecté au broker MQTT.');

            // Souscrire au topic wildcard pour tous les capteurs
            $topic = 'doorguard/sensor/+/event';
            $this->mqtt->subscribe($topic, function (string $topic, string $message) {
                $this->processMessage($topic, $message);
            }, MqttClient::QOS_AT_LEAST_ONCE);

            $this->info("Souscrit au topic: {$topic}");
            $this->info('En attente de scans de badges... (Ctrl+C pour arrêter)');

            $this->mqtt->loop(true);

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
            $this->warn("Capteur inconnu avec unique_id [{$uniqueId}], ignoré.");
            return;
        }

        // Mettre à jour le statut du capteur
        $sensor->update([
            'status' => 'online',
            'last_seen' => now(),
        ]);

        // Réponse à une commande STATUS
        if (isset($data['action']) && $data['action'] === 'status') {
            event(new SensorStatusUpdated($sensor, $data));
            $this->info("Status reçu du capteur [{$sensor->name}]: " . json_encode($data));
            return;
        }

        // Extraire l'UID du badge scanné
        $badgeUid = $data['user_id'] ?? null;

        if (!$badgeUid) {
            $this->warn("Pas de user_id dans le message, ignoré.");
            return;
        }

        // Trouver la porte associée au capteur
        $door = $sensor->door;

        // Chercher le badge dans la base de données
        $badge = Badge::where('uid', $badgeUid)->first();

        // Logique de décision d'accès
        $status = $this->resolveAccessDecision($badge, $door);
        $responseCode = $this->getResponseCode($status);

        $this->info("Décision pour badge [{$badgeUid}] sur capteur [{$sensor->name}]: {$status}");

        // Publier la réponse sur le topic de réponse du capteur
        $responseTopic = "doorguard/sensor/{$uniqueId}/response";
        if ($this->mqtt) {
            $this->mqtt->publish($responseTopic, $responseCode, MqttClient::QOS_AT_LEAST_ONCE);
            $this->info("Réponse publiée sur [{$responseTopic}]: {$responseCode}");
        }

        // Créer le log d'accès
        $accessLog = AccessLog::create([
            'badge_id' => $badge?->id,
            'door_id' => $door?->id,
            'sensor_id' => $sensor->id,
            'status' => $status,
            'badge_uid' => $badgeUid,
            'responded_at' => now(),
        ]);

        // Broadcaster l'événement en temps réel
        event(new AccessLogCreated($accessLog));

        $holderName = $badge?->holder_name ?? 'Inconnu';
        $doorName = $door?->name ?? 'N/A';
        $this->info("Log créé: {$holderName} [{$badgeUid}] → {$doorName} = {$status}");
    }

    private function resolveAccessDecision(?Badge $badge, $door): string
    {
        // Badge inconnu → REJECTED
        if (!$badge) {
            return 'rejected';
        }

        // Badge désactivé → REFUSED
        if (!$badge->is_active) {
            return 'refused';
        }

        // Pas de porte associée au capteur → REFUSED
        if (!$door) {
            return 'refused';
        }

        // Vérifier si le badge a la permission sur cette porte
        $hasPermission = $badge->doors()->where('doors.id', $door->id)->exists();

        if (!$hasPermission) {
            return 'refused';
        }

        // Tout est OK → ACCEPTED
        return 'accepted';
    }

    private function getResponseCode(string $status): string
    {
        return match ($status) {
            'accepted' => self::ACCEPTED,
            'refused' => self::REFUSED,
            'rejected' => self::REJECTED,
            default => self::REFUSED,
        };
    }
}
