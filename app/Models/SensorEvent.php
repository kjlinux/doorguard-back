<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'sensor_id',
        'status',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}
