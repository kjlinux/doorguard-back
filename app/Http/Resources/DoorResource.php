<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'location' => $this->location,
            'sensorId' => $this->sensor_id,
            'sensor' => new SensorResource($this->whenLoaded('sensor')),
            'badgesCount' => $this->whenCounted('badges', $this->badges_count ?? $this->badges()->count()),
            'badges' => BadgeResource::collection($this->whenLoaded('badges')),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
