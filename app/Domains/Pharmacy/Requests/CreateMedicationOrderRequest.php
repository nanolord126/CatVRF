<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMedicationOrderRequest extends FormRequest
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
            'pharmacy_id'        => ['required', 'integer', 'min:1'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'prescription_url'   => ['sometimes', 'url', 'max:512'],
            'delivery_address'   => ['required', 'string', 'min:5', 'max:512'],
            'delivery_at'        => ['required', 'date', 'after:now'],
            'recipient_name'     => ['required', 'string', 'max:255'],
            'recipient_phone'    => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'notes'              => ['sometimes', 'string', 'max:500'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pharmacy_id.required'    => 'Аптека обязательна.',
            'items.required'          => 'Состав заказа обязателен.',
            'delivery_address.required' => 'Адрес доставки обязателен.',
            'recipient_name.required' => 'Имя получателя обязательно.',
            'recipient_phone.required' => 'Телефон получателя обязателен.',
        ];
    }
}
