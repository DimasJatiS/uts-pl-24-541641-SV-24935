<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PriceUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Holding;
use App\Services\PortfolioCalculator;
use App\Services\PriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PortfolioController extends Controller
{
    /**
     * GET /api/portfolio/summary → nilai portofolio, modal, untung/rugi.
     *
     * Kalau penyedia harga gagal, respons tetap 200 dengan "prices_stale": true
     * dan nilai pasar null, supaya dashboard tetap bisa tampil.
     */
    public function summary(Request $request, PriceService $priceService, PortfolioCalculator $calculator): JsonResponse
    {
        $rows = $request->user()->holdings()
            ->with('coin')
            ->orderBy('coin_id')
            ->get()
            ->map(fn (Holding $holding) => [
                'holding_id' => $holding->id,
                'coin_id' => $holding->coin_id,
                'symbol' => $holding->coin->symbol,
                'name' => $holding->coin->name,
                'amount' => $holding->amount,
                'avg_buy_price_usd' => $holding->avg_buy_price_usd,
            ])
            ->all();

        $stale = false;

        try {
            $prices = $priceService->usdPrices(array_column($rows, 'coin_id'));
        } catch (PriceUnavailableException $e) {
            Log::warning('Harga kripto tidak tersedia', ['reason' => $e->getMessage()]);
            $prices = [];
            $stale = true;
        }

        return response()->json([
            'data' => ['prices_stale' => $stale] + $calculator->summarize($rows, $prices),
        ]);
    }
}
