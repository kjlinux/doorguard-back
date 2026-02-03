<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MqttController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\SensorEventController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Endpoint public pour les capteurs (pas d'auth requise)
Route::post('/sensor/event', [SensorEventController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);
    Route::get('/dashboard/hourly-activity', [DashboardController::class, 'hourlyActivity']);
    Route::get('/dashboard/sensor-activity', [DashboardController::class, 'sensorActivity']);

    // Sensor Events
    Route::get('/events', [SensorEventController::class, 'index']);
    Route::get('/events/{sensorEvent}', [SensorEventController::class, 'show']);

    // Sensors
    Route::apiResource('sensors', SensorController::class);

    // MQTT
    Route::post('/mqtt/test', [MqttController::class, 'testConnection']);
});
