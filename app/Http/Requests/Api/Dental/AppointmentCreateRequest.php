<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Dental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentCreateRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            // Обязательный Fraud Check по канону 2026
            return FraudControlService::check([
                'user_id' => auth()->id(),
                'type' => 'appointment_create',
                'ip' => request()->ip()
            ]);
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
