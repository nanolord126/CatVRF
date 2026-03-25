declare(strict_types=1);

namespace App\Http\Requests\Hotels;

use App\Http\Requests\BaseApiRequest;

/**
 * Check-In Booking Request.
 * Валидация данных для check-in в отель.
 *
 * Rules:
 * - booking_id: required, exists
 */
final class CheckInBookingRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID required',
            'booking_id.exists' => 'Booking not found',
        ];
    }
}
