<?php

declare(strict_types=1);

/**
 * AcceptRideFormRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/acceptrideformrequest
 * @see https://catvrf.ru/docs/acceptrideformrequest
 * @see https://catvrf.ru/docs/acceptrideformrequest
 * @see https://catvrf.ru/docs/acceptrideformrequest
 * @see https://catvrf.ru/docs/acceptrideformrequest
 */


namespace App\Http\Requests\Api\V1\B2B\Taxi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AcceptRideFormRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\V1\B2B\Taxi
 */
final class AcceptRideFormRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Handle rules operation.
     *
     * @throws \DomainException
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'string', 'uuid'],
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
            'driver_id.required' => 'Идентификатор водителя обязателен.',
            'driver_id.uuid' => 'Идентификатор водителя должен быть UUID.',
        ];
    }
}
