<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MQTT Broker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the MQTT broker used by the sensors
    |
    */

    'host' => env('MQTT_HOST', 'localhost'),

    'port' => (int) env('MQTT_PORT', 1883),

    'client_id' => env('MQTT_CLIENT_ID', 'doorguard-api'),

    'tls_enabled' => filter_var(env('MQTT_TLS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'auth' => [
        'enabled' => filter_var(env('MQTT_AUTH_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'username' => env('MQTT_AUTH_USERNAME'),
        'password' => env('MQTT_AUTH_PASSWORD'),
    ],
];
