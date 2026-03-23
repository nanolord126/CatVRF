<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1|max:1000',
            'payment_method' => 'required|in:yuassa,sbp,wallet,card',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Укажите товар',
            'quantity.min' => 'Минимальное количество 1',
            'payment_method.required' => 'Выберите способ оплаты',
            'payment_method.in' => 'Неподдерживаемый способ оплаты',
        ];
    }
}
