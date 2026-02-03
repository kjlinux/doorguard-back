<?php

namespace Database\Seeders;

use App\Models\Sensor;
use App\Models\SensorEvent;
use Illuminate\Database\Seeder;

class SensorEventSeeder extends Seeder
{
    public function run(): void
    {
        $sensors = Sensor::all();
        $now = now();

        // Create only 2 sensor events
        foreach ($sensors->take(2) as $index => $sensor) {
            SensorEvent::create([
                'sensor_id' => $sensor->id,
                'status' => $index === 0 ? 'open' : 'closed',
                'detected_at' => $now->copy()->subMinutes($index * 5),
            ]);
        }
    }
}
