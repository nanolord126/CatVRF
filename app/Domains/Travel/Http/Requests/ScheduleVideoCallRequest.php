<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Schedule Video Call Request
 * 
 * Validation request for scheduling video calls with guides.
 */
final class ScheduleVideoCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_uuid' => ['required', 'string', 'exists:tourism_bookings,uuid'],
            'scheduled_time' => ['required', 'date', 'after:now', 'before:+30 days'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_uuid.required' => 'Booking UUID is required',
            'booking_uuid.exists' => 'Booking not found',
            'scheduled_time.required' => 'Scheduled time is required',
            'scheduled_time.after' => 'Scheduled time must be in the future',
            'scheduled_time.before' => 'Scheduled time must be within 30 days',
        ];
    }
}
