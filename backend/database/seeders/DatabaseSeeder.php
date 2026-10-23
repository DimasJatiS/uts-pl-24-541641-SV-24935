<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * php artisan db:seed
     */
    public function run(): void
    {
        $this->call(CoinSeeder::class);

        // Akun demo HANYA untuk development lokal, tidak pernah dibuat di production.
        if (app()->environment('local')) {
            $user = User::query()->firstOrCreate(
                ['email' => 'demo@chainfolio.test'],
                ['name' => 'Demo User', 'password' => 'Demo12345'],
            );

            $user->holdings()->firstOrCreate(
                ['coin_id' => 'bitcoin'],
                ['amount' => '0.05', 'avg_buy_price_usd' => '45000'],
            );
            $user->holdings()->firstOrCreate(
                ['coin_id' => 'ethereum'],
                ['amount' => '1.2', 'avg_buy_price_usd' => '2500'],
            );
        }
    }
}
