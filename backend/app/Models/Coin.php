<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Koin yang didukung. Primary key = ID CoinGecko (mis. "bitcoin"),
 * diisi lewat CoinSeeder sehingga validasi tidak butuh internet.
 */
#[Fillable(['id', 'symbol', 'name'])]
class Coin extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return HasMany<Holding, $this>
     */
    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }
}
