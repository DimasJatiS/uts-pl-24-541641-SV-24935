<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Koin tidak bisa diganti; untuk koin lain hapus holding lalu buat yang baru.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coin_id' => ['prohibited'],
            'amount' => ['sometimes', 'required', 'numeric', 'decimal:0,10', 'gt:0', 'max:999999999999'],
            'avg_buy_price_usd' => ['sometimes', 'required', 'numeric', 'decimal:0,8', 'gte:0', 'max:9999999999'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coin_id.prohibited' => 'Koin tidak bisa diganti. Hapus holding ini lalu tambahkan koin baru.',
            'amount.gt' => 'Jumlah harus lebih dari 0.',
        ];
    }
}
