declare(strict_types=1);

namespace App\Http\Requests\Hotels;

use App\Http\Requests\BaseApiRequest;

/**
 * Check-Out Booking Request.
 * Валидация данных для check-out из отеля.
 *
 * Rules:
 * - booking_id: required, exists
 * - early_checkout: optional, boolean
 */
final class CheckOutBookingRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'early_checkout' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID required',
            'booking_id.exists' => 'Booking not found',
            'early_checkout.boolean' => 'Early checkout must be boolean',
        ];
    }
}
