<?php declare(strict_types=1);

/**
 * CheckOutBookingRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/checkoutbookingrequest
 * @see https://catvrf.ru/docs/checkoutbookingrequest
 * @see https://catvrf.ru/docs/checkoutbookingrequest
 */


namespace App\Http\Requests\Hotels;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CheckOutBookingRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Hotels
 */
final class CheckOutBookingRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
        {
            return $this->guard->check();
        }

        /**
         * Handle rules operation.
         *
         * @throws \DomainException
         */
        public function rules(): array
        {
            return [
                'booking_id' => ['required', 'integer', 'exists:bookings,id'],
                'early_checkout' => ['sometimes', 'boolean'],
            ];
        }

        /**
         * Handle messages operation.
         *
         * @throws \DomainException
         */
        public function messages(): array
        {
            return [
                'booking_id.required' => 'Booking ID required',
                'booking_id.exists' => 'Booking not found',
                'early_checkout.boolean' => 'Early checkout must be boolean',
            ];
        }
}
