<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SensorEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'sensorId' => (string) $this->sensor_id,
            'sensorName' => $this->sensor->name,
            'sensorLocation' => $this->sensor->location,
            'status' => $this->status,
            'detectedAt' => $this->detected_at->toISOString(),
        ];
    }
}
