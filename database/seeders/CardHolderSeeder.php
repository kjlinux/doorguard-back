<?php

namespace Database\Seeders;

use App\Models\CardHolder;
use Illuminate\Database\Seeder;

class CardHolderSeeder extends Seeder
{
    public function run(): void
    {
        $holders = [
            ['card_id' => 'CARD-0042', 'name' => 'John Smith'],
            ['card_id' => 'CARD-0118', 'name' => 'Sarah Connor'],
        ];

        foreach ($holders as $holder) {
            CardHolder::create($holder);
        }
    }
}
