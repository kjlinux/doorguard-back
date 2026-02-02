<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Door extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'location'];

    public function events(): HasMany
    {
        return $this->hasMany(DoorEvent::class);
    }

    public function sensors(): HasMany
    {
        return $this->hasMany(Sensor::class);
    }
}
