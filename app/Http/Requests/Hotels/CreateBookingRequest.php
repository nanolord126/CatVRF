declare(strict_types=1);

namespace App\Http\Requests\Hotels;

use App\Http\Requests\BaseApiRequest;

/**
 * Create Hotel Booking Request.
 * Валидация данных для создания бронирования отеля.
 *
 * Rules:
 * - hotel_id: required, exists
 * - room_type_id: required, exists
 * - check_in_date: required, date_format, >= today
 * - check_out_date: required, date_format, after check_in
 * - nights: required, integer, >= 1, <= 365
 * - price_per_night: required, integer, > 0, <= 10000000 (max 100000₽/night)
 */
final class CreateBookingRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hotel_id' => ['required', 'integer', 'exists:hotels,id'],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'check_in_date' => [
                'required',
                'date_format:Y-m-d',
                'date',
                'after_or_equal:today',
            ],
            'check_out_date' => [
                'required',
                'date_format:Y-m-d',
                'date',
                'after:check_in_date',
            ],
            'nights' => ['required', 'integer', 'min:1', 'max:365'],
            'price_per_night' => ['required', 'integer', 'min:1000', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'hotel_id.required' => 'Hotel ID required',
            'hotel_id.exists' => 'Hotel not found',
            'room_type_id.required' => 'Room type required',
            'room_type_id.exists' => 'Room type not found',
            'check_in_date.required' => 'Check-in date required',
            'check_in_date.date_format' => 'Check-in date format: Y-m-d',
            'check_in_date.after_or_equal' => 'Check-in must be today or later',
            'check_out_date.required' => 'Check-out date required',
            'check_out_date.date_format' => 'Check-out date format: Y-m-d',
            'check_out_date.after' => 'Check-out must be after check-in',
            'nights.required' => 'Number of nights required',
            'nights.integer' => 'Nights must be integer',
            'nights.min' => 'Minimum 1 night',
            'nights.max' => 'Maximum 365 nights',
            'price_per_night.required' => 'Price per night required',
            'price_per_night.integer' => 'Price must be integer (kopeks)',
            'price_per_night.min' => 'Price minimum 1000 kopeks (10₽)',
            'price_per_night.max' => 'Price maximum 10000000 kopeks (100000₽)',
        ];
    }
}
