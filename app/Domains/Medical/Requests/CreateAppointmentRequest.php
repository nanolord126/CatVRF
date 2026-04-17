<?php declare(strict_types=1);

namespace App\Domains\Medical\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAppointmentRequest extends FormRequest
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
            'clinic_id'          => ['required', 'integer', 'min:1'],
            'doctor_id'          => ['required', 'integer', 'min:1'],
            'service_id'         => ['required', 'integer', 'min:1'],
            'appointment_at'     => ['required', 'date', 'after:now'],
            'prepayment_amount'  => ['sometimes', 'integer', 'min:0'],
            'client_notes'       => ['sometimes', 'string', 'max:2000'],
            'metadata'           => ['sometimes', 'array'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'clinic_id.required'      => 'Клиника обязательна.',
            'doctor_id.required'      => 'Врач обязателен.',
            'service_id.required'     => 'Услуга обязательна.',
            'appointment_at.required' => 'Дата и время приёма обязательны.',
            'appointment_at.after'    => 'Дата приёма должна быть в будущем.',
        ];
    }
}
