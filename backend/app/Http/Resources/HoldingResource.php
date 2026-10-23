<?php

namespace App\Http\Resources;

use App\Models\Holding;
use App\Support\DecimalString;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Holding
 */
class HoldingResource extends JsonResource
{
    /**
     * Angka dikirim sebagai STRING (mis. "0.5") supaya presisinya tidak hilang
     * saat di-parse sebagai float di JavaScript.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coin' => CoinResource::make($this->whenLoaded('coin')),
            'amount' => DecimalString::trim((string) $this->amount),
            'avg_buy_price_usd' => DecimalString::trim((string) $this->avg_buy_price_usd),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
