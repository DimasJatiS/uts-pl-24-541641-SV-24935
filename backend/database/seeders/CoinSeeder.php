<?php

namespace Database\Seeders;

use App\Models\Coin;
use Illuminate\Database\Seeder;

/**
 * Daftar koin yang didukung. Aman dijalankan berulang kali (upsert),
 * sehingga dipanggil otomatis setiap container backend start.
 * ID harus sama dengan ID di CoinGecko.
 */
class CoinSeeder extends Seeder
{
    /** @var array<int, array{id:string, symbol:string, name:string}> */
    public const COINS = [
        ['id' => 'bitcoin', 'symbol' => 'BTC', 'name' => 'Bitcoin'],
        ['id' => 'ethereum', 'symbol' => 'ETH', 'name' => 'Ethereum'],
        ['id' => 'tether', 'symbol' => 'USDT', 'name' => 'Tether'],
        ['id' => 'binancecoin', 'symbol' => 'BNB', 'name' => 'BNB'],
        ['id' => 'solana', 'symbol' => 'SOL', 'name' => 'Solana'],
        ['id' => 'ripple', 'symbol' => 'XRP', 'name' => 'XRP'],
        ['id' => 'usd-coin', 'symbol' => 'USDC', 'name' => 'USD Coin'],
        ['id' => 'cardano', 'symbol' => 'ADA', 'name' => 'Cardano'],
        ['id' => 'dogecoin', 'symbol' => 'DOGE', 'name' => 'Dogecoin'],
        ['id' => 'tron', 'symbol' => 'TRX', 'name' => 'TRON'],
        ['id' => 'avalanche-2', 'symbol' => 'AVAX', 'name' => 'Avalanche'],
        ['id' => 'chainlink', 'symbol' => 'LINK', 'name' => 'Chainlink'],
        ['id' => 'polkadot', 'symbol' => 'DOT', 'name' => 'Polkadot'],
        ['id' => 'the-open-network', 'symbol' => 'TON', 'name' => 'Toncoin'],
        ['id' => 'litecoin', 'symbol' => 'LTC', 'name' => 'Litecoin'],
        ['id' => 'shiba-inu', 'symbol' => 'SHIB', 'name' => 'Shiba Inu'],
        ['id' => 'uniswap', 'symbol' => 'UNI', 'name' => 'Uniswap'],
        ['id' => 'near', 'symbol' => 'NEAR', 'name' => 'NEAR Protocol'],
        ['id' => 'aptos', 'symbol' => 'APT', 'name' => 'Aptos'],
        ['id' => 'sui', 'symbol' => 'SUI', 'name' => 'Sui'],
        ['id' => 'arbitrum', 'symbol' => 'ARB', 'name' => 'Arbitrum'],
        ['id' => 'optimism', 'symbol' => 'OP', 'name' => 'Optimism'],
        ['id' => 'polygon-ecosystem-token', 'symbol' => 'POL', 'name' => 'Polygon'],
    ];

    public function run(): void
    {
        Coin::query()->upsert(self::COINS, ['id'], ['symbol', 'name']);
    }
}
