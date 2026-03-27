<?php

declare(strict_types=1);


namespace App\Domains\Marketplace\Shop\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Канон 2026: Валидация создания товара (Section 2: Shop)
 */
final class StoreShopProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole(['business_owner', 'manager']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'unique:shop_products,sku,NULL,id,tenant_id,' . auth()->user()->tenant_id],
            'category' => ['required', 'string', 'in:clothes,shoes,kids,etc'],
            'price' => ['required', 'integer', 'min:0'], // В копейках
            'attributes' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'Товар с таким SKU уже существует в вашем магазине.',
        ];
    }
}
