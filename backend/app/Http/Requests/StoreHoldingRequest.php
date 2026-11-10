<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitasi data sebelum masuk ke aturan validasi.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge([
                'notes' => is_string($this->notes) ? trim($this->notes) : $this->notes,
            ]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('holding.validation_failed', [
            'actor_type' => $this->user() ? 'authenticated_user' : 'anonymous_user',
            'user_id' => $this->user()?->id,
            'action' => 'validate_create_holding',
            'reason' => 'request_validation_failed',
            'failed_fields' => array_keys($validator->errors()->toArray()),
        ]);

        throw new HttpResponseException(
            response()->json([
                'message' => 'Data yang dikirim tidak valid.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'coin_id' => [
                'required',
                'string',
                'max:64',
                Rule::exists('coins', 'id'),
                Rule::unique('holdings', 'coin_id')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999999',
            ],
            'avg_buy_price_usd' => [
                'required',
                'numeric',
                'gte:0',
                'max:9999999999',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coin_id.required' => 'Koin wajib dipilih.',
            'coin_id.exists' => 'Koin tidak dikenal. Pilih dari daftar /api/coins.',
            'coin_id.unique' => 'Koin ini sudah ada di portofolio. Edit holding yang sudah ada.',
            'amount.required' => 'Jumlah koin wajib diisi.',
            'amount.numeric' => 'Jumlah koin harus berupa angka.',
            'amount.gt' => 'Jumlah koin harus lebih dari 0.',
            'avg_buy_price_usd.required' => 'Harga beli rata-rata wajib diisi.',
            'avg_buy_price_usd.numeric' => 'Harga beli rata-rata harus berupa angka.',
            'avg_buy_price_usd.gte' => 'Harga beli tidak boleh negatif.',
        ];
    }
}
