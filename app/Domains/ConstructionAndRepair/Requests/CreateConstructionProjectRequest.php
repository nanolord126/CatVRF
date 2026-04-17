<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateConstructionProjectRequest extends FormRequest
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
            'title'             => ['required', 'string', 'min:3', 'max:255'],
            'type'              => ['required', 'string', 'in:renovation,repair,construction,demolition,finishing'],
            'address'           => ['required', 'string', 'min:5', 'max:512'],
            'area_sqm'          => ['required', 'numeric', 'min:1'],
            'budget'            => ['required', 'numeric', 'min:0'],
            'starts_at'         => ['required', 'date', 'after_or_equal:today'],
            'ends_at'           => ['sometimes', 'date', 'after:starts_at'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required'   => 'Название проекта обязательно.',
            'type.required'    => 'Тип работ обязателен.',
            'address.required' => 'Адрес объекта обязателен.',
            'area_sqm.required' => 'Площадь обязательна.',
            'budget.required'  => 'Бюджет обязателен.',
        ];
    }
}
