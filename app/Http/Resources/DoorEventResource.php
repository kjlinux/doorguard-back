<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoorEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'doorId' => (string) $this->door_id,
            'doorName' => $this->door->name,
            'status' => $this->status,
            'timestamp' => $this->timestamp->toISOString(),
            'cardId' => $this->cardHolder?->card_id ?? 'UNKNOWN',
            'cardHolder' => $this->cardHolder?->name,
        ];
    }
}
