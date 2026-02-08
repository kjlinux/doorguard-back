<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Door;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(): JsonResponse
    {
        $since = now()->subHours(24);

        $totalAccess = AccessLog::where('responded_at', '>=', $since)->count();

        $refusedAccess = AccessLog::where('responded_at', '>=', $since)
            ->whereIn('status', ['refused', 'rejected'])
            ->count();

        $activeDoors = Door::whereHas('sensor', function ($q) {
            $q->where('status', 'online');
        })->count();

        $sensorsOnline = Sensor::where('status', 'online')->count();

        return response()->json([
            'totalAccess' => $totalAccess,
            'refusedAccess' => $refusedAccess,
            'activeDoors' => $activeDoors,
            'sensorsOnline' => $sensorsOnline,
        ]);
    }

    public function hourlyActivity(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 12);
        $since = now()->subHours($hours);

        $results = AccessLog::where('responded_at', '>=', $since)
            ->select(
                DB::raw("to_char(responded_at, 'HH24:00') as hour"),
                DB::raw('COUNT(*) as events')
            )
            ->groupBy(DB::raw("to_char(responded_at, 'HH24:00')"))
            ->orderBy('hour')
            ->get();

        return response()->json([
            'hourlyActivity' => $results,
        ]);
    }

    public function doorActivity(): JsonResponse
    {
        $results = AccessLog::join('doors', 'doors.id', '=', 'access_logs.door_id')
            ->select('doors.name as door', DB::raw('COUNT(*) as events'))
            ->groupBy('doors.id', 'doors.name')
            ->orderByDesc('events')
            ->get();

        return response()->json([
            'doorActivity' => $results,
        ]);
    }
}
