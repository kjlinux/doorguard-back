<?php

namespace Database\Seeders;

use App\Models\CardHolder;
use App\Models\Door;
use App\Models\DoorEvent;
use Illuminate\Database\Seeder;

class DoorEventSeeder extends Seeder
{
    public function run(): void
    {
        $doors = Door::all();
        $cardHolders = CardHolder::all();
        $now = now();

        for ($i = 0; $i < 200; $i++) {
            $door = $doors->random();
            $cardHolder = $cardHolders->random();
            $minutesAgo = rand(0, 1440); // up to 24 hours

            DoorEvent::create([
                'door_id' => $door->id,
                'status' => rand(0, 100) > 30 ? 'open' : 'closed',
                'card_holder_id' => $cardHolder->id,
                'timestamp' => $now->copy()->subMinutes($minutesAgo),
            ]);
        }
    }
}
