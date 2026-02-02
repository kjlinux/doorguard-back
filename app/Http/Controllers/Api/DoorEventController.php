<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoorEventResource;
use App\Models\DoorEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoorEventController extends Controller
{
    public function index(Request $request)
    {
        $query = DoorEvent::with(['door', 'cardHolder'])
            ->orderByDesc('timestamp');

        if ($request->has('door_id')) {
            $query->where('door_id', $request->input('door_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $limit = (int) $request->input('limit', 10);

        $events = $query->paginate($limit);

        return DoorEventResource::collection($events);
    }

    public function show(DoorEvent $doorEvent): DoorEventResource
    {
        $doorEvent->load(['door', 'cardHolder']);

        return new DoorEventResource($doorEvent);
    }
}
