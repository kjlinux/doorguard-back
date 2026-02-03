<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestMqttRequest;
use Illuminate\Http\JsonResponse;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttController extends Controller
{
    public function testConnection(TestMqttRequest $request): JsonResponse
    {
        try {
            $mqtt = new MqttClient(
                config('mqtt.host'),
                config('mqtt.port'),
                'doorguard-test-' . uniqid()
            );

            $connectionSettings = (new ConnectionSettings)
                ->setConnectTimeout(10)
                ->setUseTls(config('mqtt.tls_enabled'))
                ->setTlsVerifyPeer(false)
                ->setTlsVerifyPeerName(false);

            if (config('mqtt.auth.enabled')) {
                $connectionSettings
                    ->setUsername(config('mqtt.auth.username'))
                    ->setPassword(config('mqtt.auth.password'));
            }

            $mqtt->connect($connectionSettings);
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
}
