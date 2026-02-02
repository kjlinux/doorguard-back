<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sensor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'door_id',
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

    public function door(): BelongsTo
    {
        return $this->belongsTo(Door::class);
    }
}
