<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_id' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required' => 'Укажите ID платежа',
        ];
    }
}
