<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — ChainFolio (FrankenPHP / Docker Entry)
|--------------------------------------------------------------------------
*/

// Endpoint Health Check untuk Docker Daemon
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Root Endpoint (JSON Info)
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'docs' => 'Lihat docs/postman untuk daftar endpoint /api.',
]));
