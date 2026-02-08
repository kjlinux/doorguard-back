<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoorRequest;
use App\Http\Requests\UpdateDoorRequest;
use App\Http\Resources\DoorResource;
use App\Models\Badge;
use App\Models\Door;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoorController extends Controller
{
    public function index()
    {
        $doors = Door::with('sensor')->withCount('badges')->orderByDesc('created_at')->get();

        return DoorResource::collection($doors);
    }

    public function store(StoreDoorRequest $request): DoorResource
    {
        $door = Door::create($request->validated());
        $door->load('sensor');

        return new DoorResource($door);
    }

    public function show(Door $door): DoorResource
    {
        $door->load(['sensor', 'badges']);
        $door->loadCount('badges');

        return new DoorResource($door);
    }

    public function update(UpdateDoorRequest $request, Door $door): DoorResource
    {
        $door->update($request->validated());
        $door->load('sensor');

        return new DoorResource($door);
    }

    public function destroy(Door $door): JsonResponse
    {
        $door->delete();

        return response()->json(null, 204);
    }

    public function assignBadges(Request $request, Door $door): JsonResponse
    {
        $validated = $request->validate([
            'badge_ids' => 'required|array',
            'badge_ids.*' => 'exists:badges,id',
        ]);

        $door->badges()->syncWithoutDetaching($validated['badge_ids']);

        return response()->json([
            'message' => 'Badges assignés avec succès',
            'door' => new DoorResource($door->load('badges')),
        ]);
    }

    public function removeBadge(Door $door, Badge $badge): JsonResponse
    {
        $door->badges()->detach($badge->id);

        return response()->json([
            'message' => 'Badge retiré avec succès',
        ]);
    }
}
