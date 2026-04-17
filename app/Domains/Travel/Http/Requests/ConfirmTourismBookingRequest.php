<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirm Tourism Booking Request
 * 
 * Validation request for confirming tourism bookings.
 */
final class ConfirmTourismBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_uuid' => ['required', 'string', 'exists:tourism_bookings,uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_uuid.required' => 'Booking UUID is required',
            'booking_uuid.exists' => 'Booking not found',
        ];
    }
}
