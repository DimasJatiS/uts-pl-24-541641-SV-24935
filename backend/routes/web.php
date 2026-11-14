<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — ChainFolio (FrankenPHP / Docker Entry)
|--------------------------------------------------------------------------
*/

// Health check untuk healthcheck Docker (curl http://localhost:8000/health).
// Memakai controller yang sama dengan /api/health supaya hasilnya identik.
Route::get('/health', HealthController::class);

// Root Endpoint (JSON Info)
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'docs' => 'Lihat docs/postman untuk daftar endpoint /api.',
]));
