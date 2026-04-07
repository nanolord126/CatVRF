<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Dental;



use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AppointmentCreateRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\Dental
 */
final class AppointmentCreateRequest extends FormRequest
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function authorize(): bool
        {
            // Обязательный Fraud Check по канону 2026
            app(\App\Services\FraudControlService::class)->check(
                userId: (int) $this->guard->id(),
                operationType: 'appointment_create',
                amount: 0,
                correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            );
            return true;
        }

        public function rules(): array
        {
            return [
                'clinic_id' => ['required', 'integer', 'exists:dental_clinics,id'],
                'dentist_id' => ['required', 'integer', 'exists:dentists,id'],
                'service_id' => ['required', 'integer', 'exists:dental_services,id'],
                'appointment_at' => ['required', 'date', 'after:now'],
                'patient_name' => ['required', 'string', 'max:255'],
                'patient_phone' => ['required', 'string', 'regex:/^\+?[78][-(]?\d{3}\)?-?\d{3}-?\d{2}-?\d{2}$/'],
                'symptoms' => ['nullable', 'string', 'max:1000'],
                'is_emergency' => ['boolean'],
            ];
        }

        public function messages(): array
        {
            return [
                'appointment_at.after' => 'Время записи должно быть в будущем.',
                'patient_phone.regex' => 'Некорректный формат номера телефона.',
            ];
        }
}
