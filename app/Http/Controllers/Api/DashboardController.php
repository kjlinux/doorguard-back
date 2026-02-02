<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoorEvent;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(): JsonResponse
    {
        $since = now()->subHours(24);

        // Total events in the last 24 hours
        $totalEvents = DoorEvent::where('timestamp', '>=', $since)->count();

        // Open doors: doors whose latest event has status "open"
        $openDoors = DB::table('door_events as de')
            ->joinSub(
                DB::table('door_events')
                    ->select('door_id', DB::raw('MAX(id) as max_id'))
                    ->groupBy('door_id'),
                'latest',
                'de.id',
                '=',
                'latest.max_id'
            )
            ->where('de.status', 'open')
            ->count();

        // Unique cards used in the last 24 hours
        $uniqueCards = DoorEvent::where('timestamp', '>=', $since)
            ->whereNotNull('card_holder_id')
            ->distinct('card_holder_id')
            ->count('card_holder_id');

        // Sensors online
        $sensorsOnline = Sensor::where('status', 'online')->count();

        return response()->json([
            'totalEvents' => $totalEvents,
            'openDoors' => $openDoors,
            'uniqueCards' => $uniqueCards,
            'sensorsOnline' => $sensorsOnline,
        ]);
    }

    public function hourlyActivity(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 12);
        $since = now()->subHours($hours);

        $results = DoorEvent::where('timestamp', '>=', $since)
            ->select(
                DB::raw("to_char(timestamp, 'HH24:00') as hour"),
                DB::raw('COUNT(*) as events')
            )
            ->groupBy(DB::raw("to_char(timestamp, 'HH24:00')"))
            ->orderBy('hour')
            ->get();

        return response()->json([
            'hourlyActivity' => $results,
        ]);
    }

    public function doorActivity(): JsonResponse
    {
        $results = DoorEvent::join('doors', 'doors.id', '=', 'door_events.door_id')
            ->select('doors.name as door', DB::raw('COUNT(*) as events'))
            ->groupBy('doors.id', 'doors.name')
            ->orderByDesc('events')
            ->get();

        return response()->json([
            'doorActivity' => $results,
        ]);
    }
}
