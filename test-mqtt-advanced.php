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

$host = getenv('MQTT_HOST');
$port = (int) getenv('MQTT_PORT');
$username = getenv('MQTT_AUTH_USERNAME');
$password = getenv('MQTT_AUTH_PASSWORD');

echo "=== Testing Different TLS Configurations ===\n\n";

// Test 1: Current settings (verify peer disabled)
echo "Test 1: TLS with peer verification disabled\n";
try {
    $mqtt = new MqttClient($host, $port, 'test-' . uniqid());
    $settings = (new ConnectionSettings)
        ->setConnectTimeout(10)
        ->setUseTls(true)
        ->setTlsVerifyPeer(false)
        ->setTlsVerifyPeerName(false)
        ->setUsername($username)
        ->setPassword($password);

    $mqtt->connect($settings);
    echo "✓ SUCCESS!\n\n";
    $mqtt->disconnect();
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 2: TLS with peer verification enabled
echo "Test 2: TLS with peer verification enabled\n";
try {
    $mqtt = new MqttClient($host, $port, 'test-' . uniqid());
    $settings = (new ConnectionSettings)
        ->setConnectTimeout(10)
        ->setUseTls(true)
        ->setTlsVerifyPeer(true)
        ->setTlsVerifyPeerName(true)
        ->setUsername($username)
        ->setPassword($password);

    $mqtt->connect($settings);
    echo "✓ SUCCESS!\n\n";
    $mqtt->disconnect();
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 3: With custom TLS context options
echo "Test 3: TLS with custom stream context\n";
try {
    $mqtt = new MqttClient($host, $port, 'test-' . uniqid());
    $settings = (new ConnectionSettings)
        ->setConnectTimeout(10)
        ->setUseTls(true)
        ->setTlsVerifyPeer(true)
        ->setTlsVerifyPeerName(true)
        ->setTlsSelfSignedAllowed(false)
        ->setUsername($username)
        ->setPassword($password);

    $mqtt->connect($settings);
    echo "✓ SUCCESS!\n\n";
    $mqtt->disconnect();
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 4: Check MQTT protocol version
echo "Test 4: Using MQTT v3.1.1 explicitly\n";
try {
    $mqtt = new MqttClient($host, $port, 'test-' . uniqid(), \PhpMqtt\Client\MqttClient::MQTT_3_1_1);
    $settings = (new ConnectionSettings)
        ->setConnectTimeout(10)
        ->setUseTls(true)
        ->setTlsVerifyPeer(false)
        ->setTlsVerifyPeerName(false)
        ->setUsername($username)
        ->setPassword($password);

    $mqtt->connect($settings);
    echo "✓ SUCCESS!\n\n";
    $mqtt->disconnect();
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 5: Check if username/password are being sent correctly
echo "Test 5: Verify authentication data\n";
echo "Username length: " . strlen($username) . "\n";
echo "Password length: " . strlen($password) . "\n";
echo "Username has whitespace: " . (preg_match('/\s/', $username) ? 'Yes' : 'No') . "\n";
echo "Password has whitespace: " . (preg_match('/\s/', $password) ? 'Yes' : 'No') . "\n";
