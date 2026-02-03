<?php

namespace Database\Seeders;

use App\Models\Door;
use App\Models\DoorEvent;
use Illuminate\Database\Seeder;

class DoorEventSeeder extends Seeder
{
    public function run(): void
    {
        $doors = Door::all();
        $now = now();

        // Create only 2 door events
        foreach ($doors->take(2) as $index => $door) {
            DoorEvent::create([
                'door_id' => $door->id,
                'status' => $index === 0 ? 'open' : 'closed',
                'timestamp' => $now->copy()->subMinutes($index * 5),
            ]);
        }
    }
}
