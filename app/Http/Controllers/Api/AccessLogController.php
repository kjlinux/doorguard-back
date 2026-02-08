<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccessLogResource;
use App\Models\AccessLog;
use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AccessLog::with(['badge', 'door', 'sensor'])
            ->orderByDesc('responded_at');

        if ($request->has('door_id')) {
            $query->where('door_id', $request->input('door_id'));
        }

        if ($request->has('badge_id')) {
            $query->where('badge_id', $request->input('badge_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('date_from')) {
            $query->where('responded_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('responded_at', '<=', $request->input('date_to'));
        }

        $limit = (int) $request->input('limit', 15);

        return AccessLogResource::collection($query->paginate($limit));
    }

    public function show(AccessLog $accessLog): AccessLogResource
    {
        $accessLog->load(['badge', 'door', 'sensor']);

        return new AccessLogResource($accessLog);
    }
}
