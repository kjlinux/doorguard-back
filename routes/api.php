<?php

use App\Http\Controllers\Api\AccessLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoorController;
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

    // Badges
    Route::apiResource('badges', BadgeController::class);
    Route::post('/badges/{badge}/toggle', [BadgeController::class, 'toggle']);

    // Doors
    Route::apiResource('doors', DoorController::class);
    Route::post('/doors/{door}/badges', [DoorController::class, 'assignBadges']);
    Route::delete('/doors/{door}/badges/{badge}', [DoorController::class, 'removeBadge']);

    // Access Logs
    Route::get('/access-logs', [AccessLogController::class, 'index']);
    Route::get('/access-logs/{accessLog}', [AccessLogController::class, 'show']);

    // Sensors
    Route::apiResource('sensors', SensorController::class);

    // MQTT Commands
    Route::post('/mqtt/test', [MqttController::class, 'testConnection']);
    Route::post('/mqtt/send-command', [MqttController::class, 'sendCommand']);
});
