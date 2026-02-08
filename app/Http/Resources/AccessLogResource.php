<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'badgeUid' => $this->badge_uid,
            'holderName' => $this->badge?->holder_name ?? 'Inconnu',
            'doorName' => $this->door?->name ?? 'Inconnue',
            'doorLocation' => $this->door?->location ?? '',
            'sensorName' => $this->sensor?->name ?? '',
            'status' => $this->status,
            'respondedAt' => $this->responded_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
