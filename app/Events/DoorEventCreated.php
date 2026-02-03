<?php

namespace App\Events;

use App\Http\Resources\DoorEventResource;
use App\Models\DoorEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DoorEventCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DoorEvent $doorEvent)
    {
        $this->doorEvent->load(['door', 'cardHolder']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('door-events'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'door.event.created';
    }

    public function broadcastWith(): array
    {
        return (new DoorEventResource($this->doorEvent))->resolve();
    }
}
