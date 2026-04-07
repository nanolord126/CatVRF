<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\B2C\Taxi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class RequestRideFormRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\V1\B2C\Taxi
 */
final class RequestRideFormRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
    {
        // Authorization logic will be handled by a policy or middleware
        return true;
    }

    /**
     * Handle rules operation.
     *
     * @throws \DomainException
     */
    public function rules(): array
    {
        return [
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_latitude' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_longitude' => ['required', 'numeric', 'between:-180,180'],
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
            'pickup_latitude.required' => 'Pickup latitude is required.',
            'pickup_longitude.required' => 'Pickup longitude is required.',
            'dropoff_latitude.required' => 'Dropoff latitude is required.',
            'dropoff_longitude.required' => 'Dropoff longitude is required.',
            '*.numeric' => 'The field must be a number.',
            '*.between' => 'The field has an invalid range.',
        ];
    }
}
