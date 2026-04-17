<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCleaningOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'service_type'      => ['required', 'string', 'in:standard,deep,windows,post_construction,office'],
            'address'           => ['required', 'string', 'min:5', 'max:512'],
            'area_sqm'          => ['required', 'numeric', 'min:1', 'max:9999'],
            'scheduled_at'      => ['required', 'date', 'after:now'],
            'duration_hours'    => ['sometimes', 'numeric', 'min:1', 'max:24'],
            'notes'             => ['sometimes', 'string', 'max:1000'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'service_type.required' => 'Тип уборки обязателен.',
            'address.required'      => 'Адрес обязателен.',
            'area_sqm.required'     => 'Площадь помещения обязательна.',
            'scheduled_at.required' => 'Дата и время уборки обязательны.',
        ];
    }
}
