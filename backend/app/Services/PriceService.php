<?php

namespace App\Services;

use App\Exceptions\PriceUnavailableException;
use App\Support\DecimalString;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Mengambil harga USD dari CoinGecko (endpoint /simple/price).
 *
 * - API key dikirim lewat header (bukan query string) supaya tidak tercatat di log URL.
 * - Hasil di-cache beberapa detik untuk menghindari rate limit.
 * - Kegagalan TIDAK di-cache, jadi request berikutnya akan mencoba lagi.
 */
class PriceService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly int $timeout,
        private readonly int $cacheTtl,
    ) {}

    /**
     * @param  array<int, string>  $coinIds
     * @return array<string, string> coin_id => harga USD (string desimal)
     *
     * @throws PriceUnavailableException
     */
    public function usdPrices(array $coinIds): array
    {
        $ids = array_values(array_unique($coinIds));
        sort($ids);

        if ($ids === []) {
            return [];
        }

        $cacheKey = 'coingecko:usd:'.sha1(implode(',', $ids));
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->timeout)
                ->withHeaders($this->apiKey ? ['x-cg-demo-api-key' => $this->apiKey] : [])
                ->get('simple/price', [
                    'ids' => implode(',', $ids),
                    'vs_currencies' => 'usd',
                    'precision' => 'full',
                ]);
        } catch (ConnectionException $e) {
            throw new PriceUnavailableException('Tidak bisa terhubung ke penyedia harga.', previous: $e);
        }

        if (! $response->successful()) {
            throw new PriceUnavailableException('Penyedia harga membalas HTTP '.$response->status().'.');
        }

        $payload = $response->json();
        $prices = self::parsePrices(is_array($payload) ? $payload : [], $ids);

        Cache::put($cacheKey, $prices, $this->cacheTtl);

        return $prices;
    }

    /**
     * Ambil harga yang valid saja dari payload CoinGecko:
     * {"bitcoin": {"usd": 67000.12}, "ethereum": {"usd": 3500}}
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $ids
     * @return array<string, string>
     */
    public static function parsePrices(array $payload, array $ids): array
    {
        $prices = [];

        foreach ($ids as $id) {
            $value = $payload[$id]['usd'] ?? null;

            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                $prices[$id] = DecimalString::fromNumber($value);
            }
        }

        return $prices;
    }
}
