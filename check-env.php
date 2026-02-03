<?php

// Read .env directly
$envContent = file_get_contents(__DIR__ . '/.env');

// Extract MQTT password line
preg_match('/MQTT_AUTH_PASSWORD=(.*)/', $envContent, $matches);
if ($matches) {
    $rawPassword = $matches[1];
    echo "Raw password from .env: [$rawPassword]\n";
    echo "Length: " . strlen($rawPassword) . "\n";
    echo "Hex: " . bin2hex($rawPassword) . "\n";

    // Check for quotes
    if (preg_match('/^["\'](.*)["\']\s*$/', $rawPassword, $quotedMatches)) {
        echo "Password appears to be quoted. Unquoted value: [" . $quotedMatches[1] . "]\n";
    }
}
