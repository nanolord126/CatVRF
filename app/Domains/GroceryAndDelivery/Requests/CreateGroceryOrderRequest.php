<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGroceryOrderRequest extends FormRequest
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
            'store_id'           => ['required', 'integer', 'min:1'],
            'items'              => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.1'],
            'delivery_address'   => ['required', 'string', 'min:5', 'max:512'],
            'delivery_slot'      => ['required', 'string', 'max:64'],
            'payment_method'     => ['required', 'string', 'in:card,sbp,wallet,cash_on_delivery'],
            'promo_code'         => ['sometimes', 'string', 'max:32'],
            'notes'              => ['sometimes', 'string', 'max:500'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'store_id.required'        => 'Магазин обязателен.',
            'items.required'           => 'Список товаров обязателен.',
            'delivery_address.required' => 'Адрес доставки обязателен.',
            'delivery_slot.required'   => 'Слот доставки обязателен.',
            'payment_method.required'  => 'Способ оплаты обязателен.',
        ];
    }
}
