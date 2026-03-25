declare(strict_types=1);

namespace App\Http\Requests\Beauty;

use App\Http\Requests\BaseApiRequest;

/**
 * Confirm Beauty Appointment Request.
 * Валидация подтверждения записи после оплаты.
 *
 * Rules:
 * - appointment_id: required, exists, belongs to current tenant
 * - payment_status: required, in:captured,authorized
 * - payment_id: required, integer
 */
final class ConfirmAppointmentRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'payment_status' => ['required', 'string', 'in:captured,authorized'],
            'payment_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.required' => 'Appointment ID required',
            'appointment_id.exists' => 'Appointment not found',
            'payment_status.required' => 'Payment status required',
            'payment_status.in' => 'Invalid payment status',
            'payment_id.required' => 'Payment ID required',
        ];
    }
}
