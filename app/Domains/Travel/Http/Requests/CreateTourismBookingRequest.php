<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Tourism Booking Request
 * 
 * Validation request for creating tourism bookings.
 * Supports B2C and B2B booking flows with split payment.
 */
final class CreateTourismBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tour_uuid' => ['required', 'string', 'exists:tours,uuid'],
            'person_count' => ['required', 'integer', 'min:1', 'max:50'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', Rule::in(['card', 'wallet', 'sbp', 'split'])],
            'split_payment_enabled' => ['boolean'],
            'business_group_id' => ['nullable', 'integer', 'exists:business_groups,id'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'tour_uuid.required' => 'Tour UUID is required',
            'tour_uuid.exists' => 'Tour not found',
            'person_count.required' => 'Person count is required',
            'person_count.min' => 'At least 1 person is required',
            'person_count.max' => 'Maximum 50 persons per booking',
            'start_date.required' => 'Start date is required',
            'start_date.after' => 'Start date must be in the future',
            'end_date.required' => 'End date is required',
            'end_date.after' => 'End date must be after start date',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method',
        ];
    }
}
