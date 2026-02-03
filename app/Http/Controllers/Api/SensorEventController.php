<?php

namespace App\Http\Controllers\Api;

use App\Events\SensorEventCreated;
use App\Http\Controllers\Controller;
use App\Http\Resources\SensorEventResource;
use App\Models\Sensor;
use App\Models\SensorEvent;
use Illuminate\Http\Request;

class SensorEventController extends Controller
{
    public function index(Request $request)
    {
        $query = SensorEvent::with(['sensor'])
            ->orderByDesc('detected_at');

        if ($request->has('sensor_id')) {
            $query->where('sensor_id', $request->input('sensor_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $limit = (int) $request->input('limit', 10);

        $events = $query->paginate($limit);

        return SensorEventResource::collection($events);
    }

    public function store(Request $request): SensorEventResource
    {
        $validated = $request->validate([
            'sensor_id' => 'required|exists:sensors,id',
            'status' => 'required|string|in:open,closed',
            'detected_at' => 'nullable|date',
        ]);

        $sensor = Sensor::findOrFail($validated['sensor_id']);

        $sensor->update([
            'status' => 'online',
            'last_seen' => now(),
        ]);

        $sensorEvent = SensorEvent::create([
            'sensor_id' => $sensor->id,
            'status' => $validated['status'],
            'detected_at' => $validated['detected_at'] ?? now(),
        ]);

        event(new SensorEventCreated($sensorEvent));

        $sensorEvent->load(['sensor']);

        return new SensorEventResource($sensorEvent);
    }

    public function show(SensorEvent $sensorEvent): SensorEventResource
    {
        $sensorEvent->load(['sensor']);

        return new SensorEventResource($sensorEvent);
    }
}
