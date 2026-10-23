<?php

namespace Tests\Feature;

use App\Models\Holding;
use App\Models\User;
use Database\Seeders\CoinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfolioSummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed di setiap test (di dalam transaksi RefreshDatabase), karena opsi $seed
        // hanya dijalankan sekali per proses dan bergantung urutan test.
        $this->seed(CoinSeeder::class);

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    private function seedHoldings(): void
    {
        Holding::factory()->for($this->user)->forCoin('bitcoin')
            ->create(['amount' => '0.5', 'avg_buy_price_usd' => '40000']);
        Holding::factory()->for($this->user)->forCoin('ethereum')
            ->create(['amount' => '2', 'avg_buy_price_usd' => '2500']);
    }

    public function test_summary_calculates_value_and_profit(): void
    {
        $this->seedHoldings();
        Http::fake([
            'api.coingecko.com/*' => Http::response([
                'bitcoin' => ['usd' => 60000],
                'ethereum' => ['usd' => 3000],
            ]),
        ]);

        $this->getJson('/api/portfolio/summary')
            ->assertOk()
            ->assertJsonPath('data.prices_stale', false)
            ->assertJsonPath('data.complete', true)
            ->assertJsonPath('data.totals.cost_usd', '25000.00')
            ->assertJsonPath('data.totals.value_usd', '36000.00')
            ->assertJsonPath('data.totals.pnl_usd', '11000.00')
            ->assertJsonPath('data.totals.pnl_percent', '44.00')
            ->assertJsonPath('data.holdings.0.coin_id', 'bitcoin')
            ->assertJsonPath('data.holdings.0.value_usd', '30000.00')
            ->assertJsonPath('data.holdings.0.pnl_percent', '50.00')
            ->assertJsonPath('data.holdings.0.allocation_percent', '83.33')
            ->assertJsonPath('data.holdings.1.coin_id', 'ethereum')
            ->assertJsonPath('data.holdings.1.pnl_usd', '1000.00')
            ->assertJsonPath('data.holdings.1.allocation_percent', '16.67');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'simple/price')
            && $request['ids'] === 'bitcoin,ethereum'
            && $request['vs_currencies'] === 'usd');
    }

    public function test_summary_survives_price_provider_failure(): void
    {
        $this->seedHoldings();
        Http::fake(['api.coingecko.com/*' => Http::response(['error' => 'rate limited'], 429)]);

        $this->getJson('/api/portfolio/summary')
            ->assertOk()
            ->assertJsonPath('data.prices_stale', true)
            ->assertJsonPath('data.complete', false)
            ->assertJsonPath('data.totals.cost_usd', '25000.00')
            ->assertJsonPath('data.totals.value_usd', null)
            ->assertJsonPath('data.holdings.0.price_usd', null);
    }

    public function test_prices_are_cached_between_requests(): void
    {
        $this->seedHoldings();
        Http::fake([
            'api.coingecko.com/*' => Http::response([
                'bitcoin' => ['usd' => 60000],
                'ethereum' => ['usd' => 3000],
            ]),
        ]);

        $this->getJson('/api/portfolio/summary')->assertOk();
        $this->getJson('/api/portfolio/summary')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_api_key_is_sent_as_header_not_query_string(): void
    {
        config(['services.coingecko.api_key' => 'kunci-rahasia']);
        $this->seedHoldings();
        Http::fake(['api.coingecko.com/*' => Http::response(['bitcoin' => ['usd' => 1], 'ethereum' => ['usd' => 1]])]);

        $this->getJson('/api/portfolio/summary')->assertOk();

        Http::assertSent(fn (Request $request) => $request->hasHeader('x-cg-demo-api-key', 'kunci-rahasia')
            && ! str_contains($request->url(), 'kunci-rahasia'));
    }

    public function test_empty_portfolio_needs_no_price_request(): void
    {
        Http::fake();

        $this->getJson('/api/portfolio/summary')
            ->assertOk()
            ->assertJsonPath('data.totals.cost_usd', '0.00')
            ->assertJsonPath('data.totals.value_usd', '0.00')
            ->assertJsonPath('data.totals.pnl_percent', null)
            ->assertJsonCount(0, 'data.holdings');

        Http::assertNothingSent();
    }

    public function test_summary_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', '')->getJson('/api/portfolio/summary')->assertUnauthorized();
    }
}
