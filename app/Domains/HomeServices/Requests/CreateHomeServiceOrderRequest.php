<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateHomeServiceOrderRequest extends FormRequest
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
            'service_type'      => ['required', 'string', 'in:plumbing,electricity,appliance_repair,handyman,locksmith,painting,pest_control'],
            'address'           => ['required', 'string', 'min:5', 'max:512'],
            'description'       => ['required', 'string', 'min:10', 'max:2000'],
            'photos'            => ['sometimes', 'array', 'max:10'],
            'photos.*'          => ['url', 'max:512'],
            'scheduled_at'      => ['required', 'date', 'after:now'],
            'is_urgent'         => ['sometimes', 'boolean'],
            'notes'             => ['sometimes', 'string', 'max:1000'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'service_type.required' => 'Тип услуги обязателен.',
            'address.required'      => 'Адрес обязателен.',
            'description.required'  => 'Описание работ обязательно.',
            'scheduled_at.required' => 'Дата и время обязательны.',
        ];
    }
}
