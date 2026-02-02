<?php

namespace Database\Seeders;

use App\Models\Door;
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
                'door_slug' => 'main-entrance',
                'mqtt_topic' => 'doorguard/sensors/main-entrance',
                'status' => 'online',
                'last_seen' => now(),
            ],
            [
                'name' => 'Server Room Sensor',
                'location' => 'Building A - Basement',
                'door_slug' => 'server-room',
                'mqtt_topic' => 'doorguard/sensors/server-room',
                'status' => 'online',
                'last_seen' => now()->subSeconds(30),
            ],
            [
                'name' => 'Office A Sensor',
                'location' => 'Building A - 2nd Floor',
                'door_slug' => 'office-a',
                'mqtt_topic' => 'doorguard/sensors/office-a',
                'status' => 'online',
                'last_seen' => now()->subSeconds(60),
            ],
            [
                'name' => 'Storage Sensor',
                'location' => 'Building B - Ground Floor',
                'door_slug' => 'storage',
                'mqtt_topic' => 'doorguard/sensors/storage',
                'status' => 'offline',
                'last_seen' => now()->subHours(1),
            ],
            [
                'name' => 'Emergency Exit Sensor',
                'location' => 'Building A - Ground Floor',
                'door_slug' => 'emergency-exit',
                'mqtt_topic' => 'doorguard/sensors/emergency',
                'status' => 'online',
                'last_seen' => now()->subSeconds(15),
            ],
        ];

        foreach ($sensors as $sensorData) {
            $door = Door::where('slug', $sensorData['door_slug'])->first();
            unset($sensorData['door_slug']);
            $sensorData['door_id'] = $door->id;
            Sensor::create($sensorData);
        }
    }
}
