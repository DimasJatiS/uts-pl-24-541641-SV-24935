<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CoinController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HoldingController;
use App\Http\Controllers\Api\PortfolioController;
use Illuminate\Support\Facades\Route;

/*
| Semua route di file ini otomatis berawalan /api.
*/

Route::get('/health', HealthController::class)->name('health');

// Endpoint publik, dibatasi rate limiter "auth" (anti brute force).
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// Endpoint yang wajib membawa header "Authorization: Bearer <token>".
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/coins', [CoinController::class, 'index'])->name('coins.index');

    Route::apiResource('holdings', HoldingController::class);

    Route::get('/portfolio/summary', [PortfolioController::class, 'summary'])->name('portfolio.summary');
});
