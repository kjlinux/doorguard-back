<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use App\Models\SensorEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(): JsonResponse
    {
        $since = now()->subHours(24);

        // Total events in the last 24 hours
        $totalEvents = SensorEvent::where('detected_at', '>=', $since)->count();

        // Open sensors: sensors whose latest event has status "open"
        $openSensors = DB::table('sensor_events as se')
            ->joinSub(
                DB::table('sensor_events')
                    ->select('sensor_id', DB::raw('MAX(id) as max_id'))
                    ->groupBy('sensor_id'),
                'latest',
                'se.id',
                '=',
                'latest.max_id'
            )
            ->where('se.status', 'open')
            ->count();

        // Sensors online
        $sensorsOnline = Sensor::where('status', 'online')->count();

        return response()->json([
            'totalEvents' => $totalEvents,
            'openSensors' => $openSensors,
            'sensorsOnline' => $sensorsOnline,
        ]);
    }

    public function hourlyActivity(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 12);
        $since = now()->subHours($hours);

        $results = SensorEvent::where('detected_at', '>=', $since)
            ->select(
                DB::raw("to_char(detected_at, 'HH24:00') as hour"),
                DB::raw('COUNT(*) as events')
            )
            ->groupBy(DB::raw("to_char(detected_at, 'HH24:00')"))
            ->orderBy('hour')
            ->get();

        return response()->json([
            'hourlyActivity' => $results,
        ]);
    }

    public function sensorActivity(): JsonResponse
    {
        $results = SensorEvent::join('sensors', 'sensors.id', '=', 'sensor_events.sensor_id')
            ->select('sensors.name as sensor', DB::raw('COUNT(*) as events'))
            ->groupBy('sensors.id', 'sensors.name')
            ->orderByDesc('events')
            ->get();

        return response()->json([
            'sensorActivity' => $results,
        ]);
    }
}
