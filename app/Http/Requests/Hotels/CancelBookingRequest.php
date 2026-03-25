declare(strict_types=1);

namespace App\Http\Requests\Hotels;

use App\Http\Requests\BaseApiRequest;

/**
 * Cancel Booking Request.
 * Валидация данных для отмены бронирования.
 *
 * Rules:
 * - booking_id: required, exists
 * - reason: optional, string (for tracking cancellation reason)
 */
final class CancelBookingRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'reason' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID required',
            'booking_id.exists' => 'Booking not found',
            'reason.string' => 'Reason must be string',
            'reason.max' => 'Reason max 500 characters',
        ];
    }
}
