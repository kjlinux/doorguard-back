<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SensorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'mqttBroker' => $this->mqtt_broker,
            'mqttPort' => $this->mqtt_port,
            'mqttTopic' => $this->mqtt_topic,
            'status' => $this->status,
            'lastSeen' => $this->last_seen?->toISOString(),
        ];
    }
}
