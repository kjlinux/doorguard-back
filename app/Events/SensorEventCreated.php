<?php

namespace App\Events;

use App\Http\Resources\SensorEventResource;
use App\Models\SensorEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorEventCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SensorEvent $sensorEvent)
    {
        $this->sensorEvent->load(['sensor']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('sensor-events'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sensor.event.created';
    }

    public function broadcastWith(): array
    {
        return (new SensorEventResource($this->sensorEvent))->resolve();
    }
}
