<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'holderName' => $this->holder_name,
            'isActive' => $this->is_active,
            'doorsCount' => $this->whenCounted('doors', $this->doors_count ?? $this->doors()->count()),
            'doors' => DoorResource::collection($this->whenLoaded('doors')),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
