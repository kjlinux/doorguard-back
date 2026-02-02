<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoorController;
use App\Http\Controllers\Api\DoorEventController;
use App\Http\Controllers\Api\MqttController;
use App\Http\Controllers\Api\SensorController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);
    Route::get('/dashboard/hourly-activity', [DashboardController::class, 'hourlyActivity']);
    Route::get('/dashboard/door-activity', [DashboardController::class, 'doorActivity']);

    // Door Events
    Route::get('/events', [DoorEventController::class, 'index']);
    Route::get('/events/{doorEvent}', [DoorEventController::class, 'show']);

    // Doors
    Route::apiResource('doors', DoorController::class);

    // Sensors
    Route::apiResource('sensors', SensorController::class);

    // MQTT
    Route::post('/mqtt/test', [MqttController::class, 'testConnection']);
});
