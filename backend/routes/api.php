<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CoinController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HoldingController;
use App\Http\Controllers\Api\PortfolioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — ChainFolio
|--------------------------------------------------------------------------
| Semua route di file ini otomatis berawalan /api
*/

// Health Check Endpoint (menggunakan HealthController)
Route::get('/health', HealthController::class)->name('health');

// Endpoint publik (Rate-limited)
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// Endpoint terproteksi (Wajib Bearer Token via Sanctum)
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/coins', [CoinController::class, 'index'])->name('coins.index');

    Route::apiResource('holdings', HoldingController::class);

    Route::get('/portfolio/summary', [PortfolioController::class, 'summary'])->name('portfolio.summary');
});