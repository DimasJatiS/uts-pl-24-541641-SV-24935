<?php

namespace App\Models;

use Database\Factories\HoldingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu koin yang dipegang seorang user.
 * user_id sengaja TIDAK fillable: selalu diisi lewat relasi $user->holdings()->create().
 */
#[Fillable(['coin_id', 'amount', 'avg_buy_price_usd', 'notes'])]
class Holding extends Model
{
    /** @use HasFactory<HoldingFactory> */
    use HasFactory;

    /**
     * Cast "decimal:N" mengembalikan STRING, bukan float.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:10',
            'avg_buy_price_usd' => 'decimal:8',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Coin, $this>
     */
    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class);
    }
}
