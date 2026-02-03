<?php

require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

// Load .env file manually
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!getenv($name)) {
                putenv("$name=$value");
            }
        }
    }
}

echo "=== MQTT Configuration Debug ===\n\n";

$host = getenv('MQTT_HOST');
$port = (int) getenv('MQTT_PORT');
$username = getenv('MQTT_AUTH_USERNAME');
$password = getenv('MQTT_AUTH_PASSWORD');
$tlsEnabled = filter_var(getenv('MQTT_TLS_ENABLED'), FILTER_VALIDATE_BOOLEAN);
$authEnabled = filter_var(getenv('MQTT_AUTH_ENABLED'), FILTER_VALIDATE_BOOLEAN);

echo "Host: $host\n";
echo "Port: $port\n";
echo "TLS Enabled: " . ($tlsEnabled ? 'Yes' : 'No') . "\n";
echo "Auth Enabled: " . ($authEnabled ? 'Yes' : 'No') . "\n";
echo "Username: $username\n";
echo "Password: " . str_repeat('*', strlen($password)) . " (length: " . strlen($password) . ")\n\n";

echo "=== Testing Connection ===\n";

try {
    $mqtt = new MqttClient(
        $host,
        $port,
        'doorguard-debug-' . uniqid()
    );

    $connectionSettings = (new ConnectionSettings)
        ->setConnectTimeout(10)
        ->setUseTls($tlsEnabled)
        ->setTlsVerifyPeer(false)
        ->setTlsVerifyPeerName(false);

    if ($authEnabled) {
        echo "Setting credentials...\n";
        $connectionSettings
            ->setUsername($username)
            ->setPassword($password);
    }

    echo "Attempting to connect...\n";
    $mqtt->connect($connectionSettings);

    echo "✓ Connection successful!\n\n";

    echo "Testing publish...\n";
    $mqtt->publish('doorguard/test', json_encode([
        'type' => 'debug-test',
        'timestamp' => date('c'),
    ]), 0);

    echo "✓ Publish successful!\n\n";

    $mqtt->disconnect();
    echo "✓ Disconnected cleanly\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "\nFull trace:\n";
    echo $e->getTraceAsString() . "\n";
}
