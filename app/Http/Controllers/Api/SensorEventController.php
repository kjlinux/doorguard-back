<?php

namespace App\Http\Controllers\Api;

use App\Events\DoorEventCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\SensorEventRequest;
use App\Http\Resources\DoorEventResource;
use App\Models\CardHolder;
use App\Models\DoorEvent;
use App\Models\Sensor;

class SensorEventController extends Controller
{
    public function store(SensorEventRequest $request): DoorEventResource
    {
        $sensor = Sensor::findOrFail($request->input('sensor_id'));

        $sensor->update([
            'status' => 'online',
            'last_seen' => now(),
        ]);

        $cardHolder = null;
        if ($request->filled('card_id')) {
            $cardHolder = CardHolder::where('card_id', $request->input('card_id'))->first();
        }

        $doorEvent = DoorEvent::create([
            'door_id' => $sensor->door_id,
            'status' => $request->input('action'),
            'card_holder_id' => $cardHolder?->id,
            'timestamp' => $request->input('timestamp', now()),
        ]);

        event(new DoorEventCreated($doorEvent));

        $doorEvent->load(['door', 'cardHolder']);

        return new DoorEventResource($doorEvent);
    }
}
