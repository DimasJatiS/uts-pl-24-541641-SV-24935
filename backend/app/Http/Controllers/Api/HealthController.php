<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * GET /api/health → dipakai healthcheck Docker dan smoke test di CI.
     * Sengaja tidak menampilkan detail error ke publik.
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (Throwable $e) {
            Log::error('Health check: database tidak bisa dihubungi', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error'], 503);
        }

        return response()->json(['status' => 'ok']);
    }
}
