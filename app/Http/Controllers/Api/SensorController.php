<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSensorRequest;
use App\Http\Requests\UpdateSensorRequest;
use App\Http\Resources\SensorResource;
use App\Models\Sensor;
use App\Models\Door;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SensorController extends Controller
{
    public function index()
    {
        return SensorResource::collection(Sensor::all());
    }

    public function store(StoreSensorRequest $request): SensorResource
    {
        $validated = $request->validated();

        // Create the sensor
        $sensor = Sensor::create([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'mqtt_topic' => $validated['mqtt_topic'],
            'mqtt_broker' => config('mqtt.host'),
            'mqtt_port' => config('mqtt.port'),
            'status' => 'offline',
        ]);

        return new SensorResource($sensor);
    }

    public function show(Sensor $sensor): SensorResource
    {
        return new SensorResource($sensor);
    }

    public function update(UpdateSensorRequest $request, Sensor $sensor): SensorResource
    {
        $sensor->update($request->validated());

        return new SensorResource($sensor);
    }

    public function destroy(Sensor $sensor): JsonResponse
    {
        $sensor->delete();

        return response()->json(null, 204);
    }
}
