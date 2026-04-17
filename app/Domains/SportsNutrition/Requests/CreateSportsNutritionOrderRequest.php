<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSportsNutritionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'  => ['nullable', 'integer', 'min:1'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'delivery_address'   => ['required', 'string', 'min:5', 'max:512'],
            'payment_method'     => ['required', 'string', 'in:card,sbp,wallet'],
            'promo_code'         => ['sometimes', 'string', 'max:32'],
            'subscription_type'  => ['sometimes', 'string', 'in:single,monthly,quarterly'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required'           => 'Состав заказа обязателен.',
            'delivery_address.required' => 'Адрес доставки обязателен.',
            'payment_method.required'  => 'Способ оплаты обязателен.',
        ];
    }
}
