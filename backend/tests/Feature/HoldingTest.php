<?php

namespace Tests\Feature;

use App\Models\Holding;
use App\Models\User;
use Database\Seeders\CoinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HoldingTest extends TestCase
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

    public function test_coins_endpoint_lists_supported_coins(): void
    {
        $this->getJson('/api/coins')
            ->assertOk()
            ->assertJsonCount(count(CoinSeeder::COINS), 'data')
            ->assertJsonFragment(['id' => 'bitcoin', 'symbol' => 'BTC']);
    }

    public function test_holdings_require_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', '')->getJson('/api/holdings')->assertUnauthorized();
    }

    public function test_user_can_create_holding(): void
    {
        $this->postJson('/api/holdings', [
            'coin_id' => 'bitcoin',
            'amount' => '0.50',
            'avg_buy_price_usd' => 60000.5,
            'notes' => 'Beli saat dip',
        ])
            ->assertCreated()
            ->assertJsonPath('data.coin.id', 'bitcoin')
            ->assertJsonPath('data.coin.symbol', 'BTC')
            ->assertJsonPath('data.amount', '0.5')
            ->assertJsonPath('data.avg_buy_price_usd', '60000.5')
            ->assertJsonPath('data.notes', 'Beli saat dip');

        $this->assertDatabaseHas('holdings', [
            'user_id' => $this->user->id,
            'coin_id' => 'bitcoin',
        ]);
    }

    public function test_create_holding_validation_returns_422(): void
    {
        $this->postJson('/api/holdings', [
            'coin_id' => 'koin-palsu',
            'amount' => -1,
            'avg_buy_price_usd' => 'abc',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['coin_id', 'amount', 'avg_buy_price_usd']);
    }

    public function test_cannot_add_the_same_coin_twice(): void
    {
        Holding::factory()->for($this->user)->forCoin('ethereum')->create();

        $this->postJson('/api/holdings', [
            'coin_id' => 'ethereum',
            'amount' => 1,
            'avg_buy_price_usd' => 3000,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['coin_id']);
    }

    public function test_index_lists_only_own_holdings(): void
    {
        Holding::factory()->for($this->user)->forCoin('bitcoin')->create();
        Holding::factory()->for($this->user)->forCoin('solana')->create();
        Holding::factory()->forCoin('ethereum')->create(); // milik user lain

        $this->getJson('/api/holdings')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => 'ethereum']);
    }

    public function test_user_can_view_own_holding(): void
    {
        $holding = Holding::factory()->for($this->user)->create(['amount' => '1.25']);

        $this->getJson("/api/holdings/{$holding->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $holding->id)
            ->assertJsonPath('data.amount', '1.25');
    }

    public function test_viewing_another_users_holding_returns_404(): void
    {
        $other = Holding::factory()->create();

        $this->getJson("/api/holdings/{$other->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Data tidak ditemukan.');
    }

    public function test_missing_holding_returns_404(): void
    {
        $this->getJson('/api/holdings/999999')->assertNotFound();
    }

    public function test_user_can_update_holding(): void
    {
        $holding = Holding::factory()->for($this->user)->create();

        $this->putJson("/api/holdings/{$holding->id}", [
            'amount' => '2',
            'avg_buy_price_usd' => '55000',
            'notes' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.amount', '2')
            ->assertJsonPath('data.avg_buy_price_usd', '55000')
            ->assertJsonPath('data.notes', null);

        $this->assertSame('2.0000000000', $holding->fresh()->amount);
    }

    public function test_update_cannot_change_coin(): void
    {
        $holding = Holding::factory()->for($this->user)->create();

        $this->putJson("/api/holdings/{$holding->id}", ['coin_id' => 'ethereum', 'amount' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['coin_id']);
    }

    public function test_updating_another_users_holding_returns_404(): void
    {
        $other = Holding::factory()->create(['amount' => '1']);

        $this->putJson("/api/holdings/{$other->id}", ['amount' => '999'])->assertNotFound();

        $this->assertSame('1.0000000000', $other->fresh()->amount);
    }

    public function test_user_can_delete_holding(): void
    {
        $holding = Holding::factory()->for($this->user)->create();

        $this->deleteJson("/api/holdings/{$holding->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Holding dihapus.');

        $this->assertModelMissing($holding);
    }

    public function test_deleting_another_users_holding_returns_404(): void
    {
        $other = Holding::factory()->create();

        $this->deleteJson("/api/holdings/{$other->id}")->assertNotFound();

        $this->assertModelExists($other);
    }
}
