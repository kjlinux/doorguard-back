<?php

namespace Database\Seeders;

use App\Models\Door;
use Illuminate\Database\Seeder;

class DoorSeeder extends Seeder
{
    public function run(): void
    {
        $doors = [
            ['name' => 'Main Entrance', 'slug' => 'main-entrance', 'location' => 'Building A - Ground Floor'],
            ['name' => 'Server Room', 'slug' => 'server-room', 'location' => 'Building A - Basement'],
        ];

        foreach ($doors as $door) {
            Door::create($door);
        }
    }
}
