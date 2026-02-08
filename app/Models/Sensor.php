<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sensor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'unique_id',
        'mqtt_broker',
        'mqtt_port',
        'mqtt_topic',
        'status',
        'last_seen',
    ];

    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
            'mqtt_port' => 'integer',
        ];
    }

    public function door(): HasOne
    {
        return $this->hasOne(Door::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SensorEvent::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }
}
