<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardHolder extends Model
{
    use HasFactory;

    protected $fillable = ['card_id', 'name'];

    public function events(): HasMany
    {
        return $this->hasMany(DoorEvent::class);
    }
}
