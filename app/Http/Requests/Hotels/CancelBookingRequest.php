<?php declare(strict_types=1);

namespace App\Http\Requests\Hotels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CancelBookingRequest extends Model
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
