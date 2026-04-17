<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCateringOrderRequest extends FormRequest
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
            'company_id'         => ['required', 'integer', 'min:1'],
            'delivery_address'   => ['required', 'string', 'min:5', 'max:512'],
            'persons_count'      => ['required', 'integer', 'min:1', 'max:10000'],
            'menu_type'          => ['required', 'string', 'in:breakfast,lunch,dinner,coffee_break,buffet,gala'],
            'dietary_options'    => ['sometimes', 'array'],
            'dietary_options.*'  => ['string', 'in:vegetarian,vegan,halal,kosher,gluten_free,dairy_free'],
            'delivery_at'        => ['required', 'date', 'after:now'],
            'is_recurring'       => ['sometimes', 'boolean'],
            'recurrence_type'    => ['required_if:is_recurring,true', 'string', 'in:daily,weekly,monthly'],
            'notes'              => ['sometimes', 'string', 'max:1000'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'company_id.required'      => 'Поставщик обязателен.',
            'delivery_address.required' => 'Адрес доставки обязателен.',
            'persons_count.required'   => 'Количество персон обязательно.',
            'menu_type.required'       => 'Тип меню обязателен.',
            'delivery_at.required'     => 'Дата и время доставки обязательны.',
        ];
    }
}
