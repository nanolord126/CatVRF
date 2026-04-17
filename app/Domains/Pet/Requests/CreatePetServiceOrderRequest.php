<?php declare(strict_types=1);

namespace App\Domains\Pet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePetServiceOrderRequest extends FormRequest
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
            'service_type'       => ['required', 'string', 'in:grooming,veterinary,boarding,training,walking,daycare,nutrition'],
            'pet_id'             => ['required', 'integer', 'min:1'],
            'provider_id'        => ['required', 'integer', 'min:1'],
            'scheduled_at'       => ['required', 'date', 'after:now'],
            'duration_minutes'   => ['sometimes', 'integer', 'min:15', 'max:1440'],
            'address'            => ['sometimes', 'string', 'max:512'],
            'notes'              => ['sometimes', 'string', 'max:1000'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'service_type.required' => 'Тип услуги обязателен.',
            'pet_id.required'       => 'Питомец обязателен.',
            'provider_id.required'  => 'Исполнитель обязателен.',
            'scheduled_at.required' => 'Дата и время обязательны.',
        ];
    }
}
