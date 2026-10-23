<?php

namespace App\Services;

use App\Support\DecimalString;
use Brick\Math\BigDecimal;

/**
 * Menghitung ringkasan portofolio. Kelas ini murni (tanpa database/HTTP)
 * supaya mudah diuji dengan unit test.
 */
final class PortfolioCalculator
{
    /**
     * @param  array<int, array{holding_id:int, coin_id:string, symbol:string, name:string, amount:string, avg_buy_price_usd:string}>  $rows
     * @param  array<string, string>  $prices  coin_id => harga USD saat ini
     * @return array{currency:string, complete:bool, totals:array<string, ?string>, holdings:array<int, array<string, mixed>>}
     */
    public function summarize(array $rows, array $prices): array
    {
        $totalCost = BigDecimal::zero();
        $totalValue = BigDecimal::zero();
        $complete = true;
        $computed = [];

        foreach ($rows as $row) {
            $amount = BigDecimal::of($row['amount']);
            $cost = $amount->multipliedBy($row['avg_buy_price_usd']);
            $price = $prices[$row['coin_id']] ?? null;
            $value = $price === null ? null : $amount->multipliedBy($price);

            $totalCost = $totalCost->plus($cost);

            if ($value === null) {
                $complete = false;
            } else {
                $totalValue = $totalValue->plus($value);
            }

            $computed[] = [$row, $cost, $price, $value];
        }

        $holdings = [];

        foreach ($computed as [$row, $cost, $price, $value]) {
            $pnl = $value?->minus($cost);

            $holdings[] = [
                'holding_id' => $row['holding_id'],
                'coin_id' => $row['coin_id'],
                'symbol' => $row['symbol'],
                'name' => $row['name'],
                'amount' => DecimalString::trim($row['amount']),
                'avg_buy_price_usd' => DecimalString::trim($row['avg_buy_price_usd']),
                'price_usd' => $price,
                'cost_usd' => DecimalString::money($cost),
                'value_usd' => $value ? DecimalString::money($value) : null,
                'pnl_usd' => $pnl ? DecimalString::money($pnl) : null,
                'pnl_percent' => $pnl ? DecimalString::percent($pnl, $cost) : null,
                'allocation_percent' => ($value && $complete) ? DecimalString::percent($value, $totalValue) : null,
            ];
        }

        $totalPnl = $totalValue->minus($totalCost);

        return [
            'currency' => 'USD',
            'complete' => $complete,
            'totals' => [
                'cost_usd' => DecimalString::money($totalCost),
                'value_usd' => $complete ? DecimalString::money($totalValue) : null,
                'pnl_usd' => $complete ? DecimalString::money($totalPnl) : null,
                'pnl_percent' => $complete ? DecimalString::percent($totalPnl, $totalCost) : null,
            ],
            'holdings' => $holdings,
        ];
    }
}
