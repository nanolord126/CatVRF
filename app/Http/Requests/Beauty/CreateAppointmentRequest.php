<?php declare(strict_types=1);

namespace App\Http\Requests\Beauty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateAppointmentRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            return auth()->check();
        }

        public function rules(): array
        {
            return [
                'beauty_salon_id' => ['required', 'integer', 'exists:beauty_salons,id'],
                'master_id' => ['required', 'integer', 'exists:masters,id'],
                'service_id' => ['required', 'integer', 'exists:services,id'],
                'appointment_datetime' => [
                    'required',
                    'date_format:Y-m-d H:i:s',
                    'after_or_equal:now',
                ],
                'price' => ['required', 'integer', 'min:1000', 'max:1000000'],
            ];
        }

        public function messages(): array
        {
            return [
                'beauty_salon_id.required' => 'Salon ID is required',
                'beauty_salon_id.exists' => 'Salon not found',
                'master_id.required' => 'Master ID is required',
                'master_id.exists' => 'Master not found',
                'service_id.required' => 'Service ID is required',
                'service_id.exists' => 'Service not found',
                'appointment_datetime.required' => 'Appointment date and time required',
                'appointment_datetime.date_format' => 'Invalid date format (use Y-m-d H:i:s)',
                'appointment_datetime.after_or_equal' => 'Appointment must be in future',
                'price.required' => 'Price is required',
                'price.integer' => 'Price must be integer (kopeks)',
                'price.min' => 'Price minimum is 1000 kopeks (10₽)',
                'price.max' => 'Price maximum is 1000000 kopeks (10000₽)',
            ];
        }
}
