<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMeatOrderRequest extends FormRequest
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
            'shop_id'            => ['required', 'integer', 'min:1'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.weight_kg'  => ['required', 'numeric', 'min:0.1', 'max:100'],
            'items.*.cut_type'   => ['sometimes', 'string', 'max:128'],
            'delivery_address'   => ['required', 'string', 'min:5', 'max:512'],
            'delivery_at'        => ['required', 'date', 'after:now'],
            'packaging'          => ['sometimes', 'string', 'in:standard,vacuum,frozen'],
            'notes'              => ['sometimes', 'string', 'max:1000'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'shop_id.required'         => 'Магазин обязателен.',
            'items.required'           => 'Состав заказа обязателен.',
            'delivery_address.required' => 'Адрес доставки обязателен.',
            'delivery_at.required'     => 'Дата и время доставки обязательны.',
        ];
    }
}
