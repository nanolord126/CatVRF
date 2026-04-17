<?php declare(strict_types=1);

namespace App\Domains\Flowers\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBouquetOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'   => ['nullable', 'integer', 'min:1'],
            'shop_id'             => ['required', 'integer', 'min:1'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', 'min:1'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
            'delivery_address'    => ['sometimes', 'string', 'max:512'],
            'delivery_at'         => ['required', 'date', 'after:now'],
            'recipient_name'      => ['required', 'string', 'max:255'],
            'recipient_phone'     => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'postcard_message'    => ['sometimes', 'string', 'max:500'],
            'is_anonymous'        => ['sometimes', 'boolean'],
            'tags'                => ['sometimes', 'array'],
            'tags.*'              => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'shop_id.required'        => 'Магазин цветов обязателен.',
            'items.required'          => 'Состав заказа обязателен.',
            'delivery_at.required'    => 'Дата и время доставки обязательны.',
            'recipient_name.required' => 'Имя получателя обязательно.',
            'recipient_phone.required' => 'Телефон получателя обязателен.',
        ];
    }
}
