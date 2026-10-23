<?php

use Illuminate\Support\Facades\Route;

// Backend ini murni API. Antarmuka ada di aplikasi Vue (folder frontend/).
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'docs' => 'Lihat docs/postman untuk daftar endpoint /api.',
]));
