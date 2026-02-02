<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoorEvent extends Model
{
    use HasFactory;

    protected $fillable = ['door_id', 'status', 'card_holder_id', 'timestamp'];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    public function door(): BelongsTo
    {
        return $this->belongsTo(Door::class);
    }

    public function cardHolder(): BelongsTo
    {
        return $this->belongsTo(CardHolder::class);
    }
}
