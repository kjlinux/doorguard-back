<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBadgeRequest;
use App\Http\Requests\UpdateBadgeRequest;
use App\Http\Resources\BadgeResource;
use App\Models\Badge;
use Illuminate\Http\JsonResponse;

class BadgeController extends Controller
{
    public function index()
    {
        $badges = Badge::withCount('doors')->orderByDesc('created_at')->get();

        return BadgeResource::collection($badges);
    }

    public function store(StoreBadgeRequest $request): BadgeResource
    {
        $badge = Badge::create($request->validated());

        return new BadgeResource($badge);
    }

    public function show(Badge $badge): BadgeResource
    {
        $badge->load('doors');
        $badge->loadCount('doors');

        return new BadgeResource($badge);
    }

    public function update(UpdateBadgeRequest $request, Badge $badge): BadgeResource
    {
        $badge->update($request->validated());

        return new BadgeResource($badge);
    }

    public function destroy(Badge $badge): JsonResponse
    {
        $badge->delete();

        return response()->json(null, 204);
    }

    public function toggle(Badge $badge): BadgeResource
    {
        $badge->update(['is_active' => !$badge->is_active]);

        return new BadgeResource($badge);
    }
}
