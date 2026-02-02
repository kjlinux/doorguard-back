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
            ['card_id' => 'CARD-0233', 'name' => 'Mike Johnson'],
            ['card_id' => 'CARD-0456', 'name' => 'Emily Davis'],
            ['card_id' => 'CARD-0789', 'name' => 'Alex Chen'],
        ];

        foreach ($holders as $holder) {
            CardHolder::create($holder);
        }
    }
}
