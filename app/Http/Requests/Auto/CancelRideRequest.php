declare(strict_types=1);

namespace App\Http\Requests\Auto;

use App\Http\Requests\BaseApiRequest;

/**
 * Cancel Taxi Ride Request.
 * Валидация данных для отмены поездки.
 *
 * Rules:
 * - ride_id: required, exists
 * - reason: optional, string
 */
final class CancelRideRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'ride_id' => ['required', 'integer', 'exists:taxi_rides,id'],
            'reason' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'ride_id.required' => 'Ride ID required',
            'ride_id.exists' => 'Ride not found',
            'reason.string' => 'Reason must be string',
            'reason.max' => 'Reason max 500 characters',
        ];
    }
}
