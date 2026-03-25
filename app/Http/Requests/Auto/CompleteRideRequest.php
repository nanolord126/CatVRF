declare(strict_types=1);

namespace App\Http\Requests\Auto;

use App\Http\Requests\BaseApiRequest;

/**
 * Complete Taxi Ride Request.
 * Валидация данных для завершения поездки.
 *
 * Rules:
 * - ride_id: required, exists
 */
final class CompleteRideRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'ride_id' => ['required', 'integer', 'exists:taxi_rides,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ride_id.required' => 'Ride ID required',
            'ride_id.exists' => 'Ride not found',
        ];
    }
}
