<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CoinResource;
use App\Models\Coin;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CoinController extends Controller
{
    /**
     * GET /api/coins → daftar koin yang didukung (untuk dropdown di frontend).
     */
    public function index(): AnonymousResourceCollection
    {
        return CoinResource::collection(Coin::query()->orderBy('name')->get());
    }
}
