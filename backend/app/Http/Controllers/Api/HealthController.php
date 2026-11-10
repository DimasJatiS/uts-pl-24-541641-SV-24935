<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * GET /health atau /api/health → Dipakai oleh healthcheck Docker & CI smoke test.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => 'healthy',
            'cache' => 'healthy',
        ];

        // 1. Tes Koneksi Database MySQL
        try {
            DB::select('select 1');
        } catch (Throwable $e) {
            $checks['database'] = 'unhealthy';
            Log::error('health.database_failed', ['exception' => get_class($e), 'message' => $e->getMessage()]);
        }

        // 2. Tes Operasi Cache (Menggunakan Durasi Expiration yang Aman)
        try {
            $cacheKey = 'health_test_'.bin2hex(random_bytes(4));
            Cache::put($cacheKey, 'ok', now()->addSeconds(30));

            if (Cache::get($cacheKey) !== 'ok') {
                $checks['cache'] = 'unhealthy';
            }

            Cache::forget($cacheKey);
        } catch (Throwable $e) {
            $checks['cache'] = 'unhealthy';
            Log::error('health.cache_failed', ['exception' => get_class($e), 'message' => $e->getMessage()]);
        }

        // Container tetap dianggap HEALTHY (200 OK) selama database hidup
        $healthy = ($checks['database'] === 'healthy');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
