<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Endpoint sudah dilindungi auth:sanctum; kepemilikan diatur di controller.
        return true;
    }

    /**
     * Angka diterima sebagai string atau number, lalu disimpan sebagai DECIMAL
     * (bukan float) supaya tidak ada pembulatan aneh.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coin_id' => [
                'required', 'string', 'max:64',
                Rule::exists('coins', 'id'),
                Rule::unique('holdings', 'coin_id')->where('user_id', $this->user()->id),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,10', 'gt:0', 'max:999999999999'],
            'avg_buy_price_usd' => ['required', 'numeric', 'decimal:0,8', 'gte:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coin_id.exists' => 'Koin tidak dikenal. Pilih dari daftar /api/coins.',
            'coin_id.unique' => 'Koin ini sudah ada di portofolio. Edit holding yang sudah ada.',
            'amount.gt' => 'Jumlah harus lebih dari 0.',
        ];
    }
}
