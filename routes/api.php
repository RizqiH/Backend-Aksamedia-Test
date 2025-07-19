<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Health check endpoint - Check API, Database, and System status
Route::get('/health', function () {
    $health = [
        'status' => 'success',
        'message' => 'API is running',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
    ];

    // Check database connection
    try {
        DB::connection()->getPdo();
        $health['database'] = [
            'status' => 'connected',
            'connection' => config('database.default'),
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => config('database.connections.mysql.database'),
        ];

        // Test a simple query
        $dbTest = DB::select('SELECT 1 as test');
        $health['database']['query_test'] = $dbTest ? 'passed' : 'failed';

    } catch (\Exception $e) {
        $health['status'] = 'warning';
        $health['database'] = [
            'status' => 'disconnected',
            'error' => $e->getMessage(),
            'connection' => config('database.default'),
        ];
    }

    // Memory usage (optional)
    $health['memory_usage'] = [
        'current' => memory_get_usage(true) / 1024 / 1024 . ' MB',
        'peak' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB',
    ];

    // Return appropriate HTTP status
    $httpStatus = ($health['status'] === 'success') ? 200 : 503;

    return response()->json($health, $httpStatus);
});

// Database health check endpoint
Route::get('/health/database', function () {
    try {
        $startTime = microtime(true);

        // Test database connection
        DB::connection()->getPdo();

        // Test simple query
        $result = DB::select('SELECT VERSION() as version, NOW() as current_time');

        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2); // ms

        return response()->json([
            'status' => 'connected',
            'connection' => config('database.default'),
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => config('database.connections.mysql.database'),
            'mysql_version' => $result[0]->version ?? 'unknown',
            'server_time' => $result[0]->current_time ?? null,
            'response_time_ms' => $responseTime,
            'timestamp' => now()->toISOString(),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'disconnected',
            'error' => $e->getMessage(),
            'connection' => config('database.default'),
            'timestamp' => now()->toISOString(),
        ], 503);
    }
});

// API status endpoint (lightweight)
Route::get('/status', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Employee Management API',
        'version' => '1.0.0',
        'environment' => app()->environment(),
        'timestamp' => now()->toISOString(),
    ], 200);
});

// Public routes (accessible without authentication) - token-based auth
Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:5,1']); // Rate limit 5 attempts per minute

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
        ]);
    });

    // Division routes
    Route::get('/divisions', [DivisionController::class, 'index']);

    // Employee routes - RESTful API
    Route::apiResource('employees', EmployeeController::class)->except(['show']);
});
