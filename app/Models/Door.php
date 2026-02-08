<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Door extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'location', 'sensor_id'];

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)->withTimestamps();
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DoorEvent::class);
    }
}
