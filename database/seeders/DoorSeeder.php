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
            ['name' => 'Office A', 'slug' => 'office-a', 'location' => 'Building A - 2nd Floor'],
            ['name' => 'Storage', 'slug' => 'storage', 'location' => 'Building B - Ground Floor'],
            ['name' => 'Emergency Exit', 'slug' => 'emergency-exit', 'location' => 'Building A - Ground Floor'],
        ];

        foreach ($doors as $door) {
            Door::create($door);
        }
    }
}
