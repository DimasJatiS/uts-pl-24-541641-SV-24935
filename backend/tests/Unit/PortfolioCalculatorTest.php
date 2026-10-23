<?php

namespace Tests\Unit;

use App\Services\PortfolioCalculator;
use App\Services\PriceService;
use App\Support\DecimalString;
use PHPUnit\Framework\TestCase;

class PortfolioCalculatorTest extends TestCase
{
    private function row(int $id, string $coin, string $amount, string $avg): array
    {
        return [
            'holding_id' => $id,
            'coin_id' => $coin,
            'symbol' => strtoupper(substr($coin, 0, 3)),
            'name' => ucfirst($coin),
            'amount' => $amount,
            'avg_buy_price_usd' => $avg,
        ];
    }

    public function test_decimal_math_has_no_float_rounding_errors(): void
    {
        $summary = (new PortfolioCalculator)->summarize(
            [$this->row(1, 'bitcoin', '0.1', '0.2'), $this->row(2, 'ethereum', '0.2', '0.1')],
            ['bitcoin' => '0.3', 'ethereum' => '0.3'],
        );

        // 0.1×0.2 + 0.2×0.1 = 0.04 (dengan float hasilnya 0.04000000000000001)
        $this->assertSame('0.04', $summary['totals']['cost_usd']);
        $this->assertSame('0.09', $summary['totals']['value_usd']);
        $this->assertSame('125.00', $summary['totals']['pnl_percent']);
    }

    public function test_loss_is_negative(): void
    {
        $summary = (new PortfolioCalculator)->summarize(
            [$this->row(1, 'solana', '10', '200')],
            ['solana' => '150'],
        );

        $this->assertSame('-500.00', $summary['holdings'][0]['pnl_usd']);
        $this->assertSame('-25.00', $summary['holdings'][0]['pnl_percent']);
        $this->assertSame('100.00', $summary['holdings'][0]['allocation_percent']);
    }

    public function test_missing_price_marks_summary_incomplete(): void
    {
        $summary = (new PortfolioCalculator)->summarize(
            [$this->row(1, 'bitcoin', '1', '100'), $this->row(2, 'ethereum', '1', '100')],
            ['bitcoin' => '150'],
        );

        $this->assertFalse($summary['complete']);
        $this->assertNull($summary['totals']['value_usd']);
        $this->assertSame('150.00', $summary['holdings'][0]['value_usd']);
        $this->assertNull($summary['holdings'][0]['allocation_percent']);
        $this->assertNull($summary['holdings'][1]['value_usd']);
    }

    public function test_free_coins_have_no_profit_percentage(): void
    {
        $summary = (new PortfolioCalculator)->summarize(
            [$this->row(1, 'tether', '50', '0')],
            ['tether' => '1'],
        );

        $this->assertSame('50.00', $summary['holdings'][0]['pnl_usd']);
        $this->assertNull($summary['holdings'][0]['pnl_percent']);
    }

    public function test_price_parser_ignores_invalid_values(): void
    {
        $prices = PriceService::parsePrices(
            ['bitcoin' => ['usd' => 67000.12], 'ethereum' => ['usd' => 'abc'], 'shiba-inu' => ['usd' => 0.00001234]],
            ['bitcoin', 'ethereum', 'shiba-inu', 'solana'],
        );

        $this->assertSame(['bitcoin' => '67000.12', 'shiba-inu' => '0.00001234'], $prices);
    }

    public function test_decimal_string_trims_trailing_zeros(): void
    {
        $this->assertSame('0.5', DecimalString::trim('0.5000000000'));
        $this->assertSame('100', DecimalString::trim('100.00'));
    }
}
