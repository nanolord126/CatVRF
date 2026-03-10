<?php

declare(strict_types=1);

namespace App\Domains\Finances\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePaymentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:pending,processing,completed,failed,refunded',
            'description' => 'nullable|string|max:500',
        ];
    }
}
