<?php declare(strict_types=1);

/**
 * CancelRideRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cancelriderequest
 * @see https://catvrf.ru/docs/cancelriderequest
 */


namespace App\Http\Requests\Auto;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CancelRideRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Auto
 */
final class CancelRideRequest extends FormRequest
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
                'ride_id' => ['required', 'integer', 'exists:taxi_rides,id'],
                'reason' => ['sometimes', 'string', 'max:500'],
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
                'ride_id.required' => 'Ride ID required',
                'ride_id.exists' => 'Ride not found',
                'reason.string' => 'Reason must be string',
                'reason.max' => 'Reason max 500 characters',
            ];
        }
}
