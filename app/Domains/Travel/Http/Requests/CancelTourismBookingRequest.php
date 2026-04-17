<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancel Tourism Booking Request
 * 
 * Validation request for cancelling tourism bookings.
 */
final class CancelTourismBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_uuid' => ['required', 'string', 'exists:tourism_bookings,uuid'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_uuid.required' => 'Booking UUID is required',
            'booking_uuid.exists' => 'Booking not found',
            'reason.required' => 'Cancellation reason is required',
            'reason.min' => 'Reason must be at least 5 characters',
            'reason.max' => 'Reason must not exceed 500 characters',
        ];
    }
}
