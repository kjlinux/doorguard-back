<?php

namespace App\Events;

use App\Http\Resources\AccessLogResource;
use App\Models\AccessLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccessLogCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AccessLog $accessLog)
    {
        $this->accessLog->load(['badge', 'door', 'sensor']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('access-logs'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'access.log.created';
    }

    public function broadcastWith(): array
    {
        return (new AccessLogResource($this->accessLog))->resolve();
    }
}
