<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateVeterinaryAppointmentRequest extends FormRequest
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
            'clinic_id'           => ['required', 'integer', 'min:1'],
            'vet_id'              => ['required', 'integer', 'min:1'],
            'pet_id'              => ['required', 'integer', 'min:1'],
            'service_type'        => ['required', 'string', 'in:examination,vaccination,surgery,grooming,dentistry,diagnostics,therapy'],
            'appointment_at'      => ['required', 'date', 'after:now'],
            'symptoms'            => ['sometimes', 'string', 'max:2000'],
            'anamnesis_notes'     => ['sometimes', 'string', 'max:4000'],
            'is_urgent'           => ['sometimes', 'boolean'],
            'prepayment_amount'   => ['sometimes', 'numeric', 'min:0'],
            'tags'                => ['sometimes', 'array'],
            'tags.*'              => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'clinic_id.required'      => 'Ветеринарная клиника обязательна.',
            'vet_id.required'         => 'Ветеринар обязателен.',
            'pet_id.required'         => 'Питомец обязателен.',
            'service_type.required'   => 'Тип услуги обязателен.',
            'appointment_at.required' => 'Дата и время записи обязательны.',
        ];
    }
}
