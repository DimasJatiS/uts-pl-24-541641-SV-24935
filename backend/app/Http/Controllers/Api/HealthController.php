<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class HealthController extends Controller
{
    /**
     * GET /health dan /api/health.
     * Dipakai healthcheck Docker dan smoke test CI. Membalas 200 bila basis data
     * dan cache sama-sama sehat, 503 bila salah satunya bermasalah.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => 'healthy',
            'cache' => 'healthy',
        ];

        // 1. Basis data: kueri termurah yang tetap memaksa koneksi benar-benar terjadi.
        try {
            DB::select('select 1');
        } catch (Throwable $e) {
            $checks['database'] = 'unhealthy';

            Log::error('health.database_failed', [
                'actor_type' => 'system',
                'action' => 'health_check_database',
                'reason' => 'database_connection_or_query_failed',
                'exception' => get_class($e),
            ]);
        }

        // 2. Cache: siklus tulis, baca ulang, hapus dengan kunci acak.
        $cacheKey = 'health-check-'.bin2hex(random_bytes(8));

        try {
            Cache::put($cacheKey, 'ok', 10);

            if (Cache::get($cacheKey) !== 'ok') {
                throw new RuntimeException('Cache read-back mismatch.');
            }

            Cache::forget($cacheKey);
        } catch (Throwable $e) {
            $checks['cache'] = 'unhealthy';

            Log::error('health.cache_failed', [
                'actor_type' => 'system',
                'action' => 'health_check_cache',
                'reason' => 'cache_write_or_read_failed',
                'exception' => get_class($e),
            ]);
        }

        $healthy = $checks['database'] === 'healthy'
            && $checks['cache'] === 'healthy';

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
