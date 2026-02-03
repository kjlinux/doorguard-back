<?php

namespace Database\Seeders;

use App\Models\Sensor;
use Illuminate\Database\Seeder;

class SensorSeeder extends Seeder
{
    public function run(): void
    {
        $sensors = [
            [
                'name' => 'Main Entrance Sensor',
                'location' => 'Building A - Ground Floor',
                'mqtt_topic' => 'doorguard/sensor/1/event',
                'mqtt_broker' => env('MQTT_HOST'),
                'mqtt_port' => env('MQTT_PORT', 8883),
                'status' => 'offline',
                'last_seen' => null,
            ],
            [
                'name' => 'Server Room Sensor',
                'location' => 'Building A - Basement',
                'mqtt_topic' => 'doorguard/sensor/2/event',
                'mqtt_broker' => env('MQTT_HOST'),
                'mqtt_port' => env('MQTT_PORT', 8883),
                'status' => 'offline',
                'last_seen' => null,
            ],
        ];

        foreach ($sensors as $sensorData) {
            Sensor::create($sensorData);
        }
    }
}
