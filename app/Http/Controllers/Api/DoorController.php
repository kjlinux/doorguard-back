<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoorRequest;
use App\Http\Requests\UpdateDoorRequest;
use App\Http\Resources\DoorResource;
use App\Models\Door;
use Illuminate\Http\JsonResponse;

class DoorController extends Controller
{
    public function index()
    {
        return DoorResource::collection(Door::all());
    }

    public function store(StoreDoorRequest $request): DoorResource
    {
        $door = Door::create($request->validated());

        return new DoorResource($door);
    }

    public function show(Door $door): DoorResource
    {
        return new DoorResource($door);
    }

    public function update(UpdateDoorRequest $request, Door $door): DoorResource
    {
        $door->update($request->validated());

        return new DoorResource($door);
    }

    public function destroy(Door $door): JsonResponse
    {
        $door->delete();

        return response()->json(null, 204);
    }
}
