declare(strict_types=1);

namespace App\Http\Requests\Beauty;

use App\Http\Requests\BaseApiRequest;

/**
 * Create Beauty Appointment Request.
 * Валидация данных для создания записи на услугу красоты.
 *
 * Rules:
 * - beauty_salon_id: required, exists in beauty_salons table
 * - master_id: required, exists in masters table
 * - service_id: required, exists in services table
 * - appointment_datetime: required, date_format, in future
 * - price: required, integer, > 0, <= 1000000 (max 10000₽)
 */
final class CreateAppointmentRequest extends BaseApiRequest
{
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
