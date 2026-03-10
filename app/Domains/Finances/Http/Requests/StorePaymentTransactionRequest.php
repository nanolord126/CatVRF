<?php

declare(strict_types=1);

namespace App\Domains\Finances\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePaymentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|uuid',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'description' => 'nullable|string|max:500',
            'reference_id' => 'nullable|string|unique:payment_transactions',
            'status' => 'nullable|string|in:pending,processing,completed,failed,refunded',
            'payment_method' => 'nullable|string|max:100',
        ];
    }
}
