<?php

namespace Database\Factories;

use App\Models\Holding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Catatan: tabel coins harus sudah terisi (CoinSeeder) sebelum factory ini dipakai.
 *
 * @extends Factory<Holding>
 */
class HoldingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'coin_id' => 'bitcoin',
            'amount' => (string) fake()->randomFloat(4, 0.01, 10),
            'avg_buy_price_usd' => (string) fake()->randomFloat(2, 100, 60000),
            'notes' => null,
        ];
    }

    public function forCoin(string $coinId): static
    {
        return $this->state(fn () => ['coin_id' => $coinId]);
    }
}
