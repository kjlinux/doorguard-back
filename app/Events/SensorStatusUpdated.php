<?php

namespace App\Events;

use App\Models\Sensor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Sensor $sensor,
        public array $statusData
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('sensor-events'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sensor.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'sensorId' => (string) $this->sensor->id,
            'sensorName' => $this->sensor->name,
            'sensorLocation' => $this->sensor->location,
            'uniqueId' => $this->sensor->unique_id,
            'status' => 'online',
            'lastSeen' => $this->sensor->last_seen?->toISOString(),
            'data' => $this->statusData,
        ];
    }
}
