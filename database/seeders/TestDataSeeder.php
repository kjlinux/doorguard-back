<?php

namespace Database\Seeders;

use App\Models\Sensor;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Créer des capteurs (max 2)
        $sensor1 = Sensor::firstOrCreate(
            ['mqtt_topic' => 'doorguard/sensor/1/event'],
            [
                'name' => 'Capteur Principal',
                'location' => 'Entrée principale',
                'mqtt_broker' => env('MQTT_HOST'),
                'mqtt_port' => env('MQTT_PORT', 8883),
                'status' => 'offline'
            ]
        );

        $sensor2 = Sensor::firstOrCreate(
            ['mqtt_topic' => 'doorguard/sensor/2/event'],
            [
                'name' => 'Capteur Bureau',
                'location' => 'Bureau 1',
                'mqtt_broker' => env('MQTT_HOST'),
                'mqtt_port' => env('MQTT_PORT', 8883),
                'status' => 'offline'
            ]
        );

        $this->command->info('✅ Données de test créées avec succès!');
        $this->command->info('');
        $this->command->info('Capteurs:');
        $this->command->info("  - {$sensor1->name} (ID: {$sensor1->id})");
        $this->command->info("  - {$sensor2->name} (ID: {$sensor2->id})");
        $this->command->info('');
        $this->command->info('Topics MQTT:');
        $this->command->info('  - doorguard/sensor/1/event (Entrée Principale)');
        $this->command->info('  - doorguard/sensor/2/event (Bureau 1)');
    }
}
