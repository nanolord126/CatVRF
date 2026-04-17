<?php declare(strict_types=1);

namespace App\Domains\Confectionery\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBakeryOrderRequest extends FormRequest
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
            'bakery_id'          => ['required', 'integer', 'min:1'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'delivery_address'   => ['sometimes', 'string', 'max:512'],
            'pickup_at'          => ['required', 'date', 'after:now'],
            'inscription'        => ['sometimes', 'string', 'max:255'],
            'notes'              => ['sometimes', 'string', 'max:1000'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bakery_id.required'  => 'Пекарня обязательна.',
            'items.required'      => 'Состав заказа обязателен.',
            'pickup_at.required'  => 'Дата получения обязательна.',
        ];
    }
}
