<?php

namespace App\Http\Controllers\Api;

use App\Events\AccessLogCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMqttCommandRequest;
use App\Http\Requests\TestMqttRequest;
use App\Models\AccessLog;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttController extends Controller
{
    private const COMMAND_CODES = [
        'FORCED_OPEN' => '0x004040J',
        'REBOOT' => '0x108091S',
        'RESET' => '0x1080713',
        'SLEEP' => '0x1080B17',
        'WAKE_UP' => '0x1080A1T',
        'STATUS' => '0x1000119',
    ];

    public function testConnection(TestMqttRequest $request): JsonResponse
    {
        try {
            $mqtt = $this->createMqttClient('doorguard-test-' . uniqid());
            $mqtt->publish($request->input('topic'), json_encode([
                'type' => 'test',
                'timestamp' => now()->toISOString(),
            ]));
            $mqtt->disconnect();

            return response()->json([
                'success' => true,
                'message' => 'Connexion MQTT reussie',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connexion MQTT echouee: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function sendCommand(SendMqttCommandRequest $request): JsonResponse
    {
        $sensor = Sensor::findOrFail($request->input('sensor_id'));
        $command = $request->input('command');
        $code = self::COMMAND_CODES[$command] ?? null;

        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Commande inconnue'], 422);
        }

        // Build response topic from sensor's mqtt_topic
        // doorguard/sensor/{unique_id}/event → doorguard/sensor/{unique_id}/response
        $responseTopic = str_replace('/event', '/response', $sensor->mqtt_topic);

        try {
            $mqtt = $this->createMqttClient('doorguard-cmd-' . uniqid());
            $mqtt->publish($responseTopic, $code);
            $mqtt->disconnect();

            // If FORCED_OPEN, create an access log
            if ($command === 'FORCED_OPEN') {
                $door = $sensor->door;
                $accessLog = AccessLog::create([
                    'badge_id' => null,
                    'door_id' => $door?->id,
                    'sensor_id' => $sensor->id,
                    'status' => 'forced_open',
                    'badge_uid' => 'REMOTE',
                    'responded_at' => now(),
                ]);

                event(new AccessLogCreated($accessLog));
            }

            return response()->json([
                'success' => true,
                'message' => "Commande {$command} envoyée au capteur {$sensor->name}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur envoi commande: ' . $e->getMessage(),
            ], 422);
        }
    }

    private function createMqttClient(string $clientId): MqttClient
    {
        $host = config('mqtt.host');
        $port = (int) config('mqtt.port', 8883);
        $tlsEnabled = config('mqtt.tls_enabled', false);
        $username = config('mqtt.auth.username');
        $password = config('mqtt.auth.password');

        $mqtt = new MqttClient($host, $port, $clientId, MqttClient::MQTT_3_1_1);

        $connectionSettings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setConnectTimeout(30)
            ->setKeepAliveInterval(10);

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

        $mqtt->connect($connectionSettings);

        return $mqtt;
    }
}
