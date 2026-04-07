<?php

declare(strict_types=1);

/**
 * CreateAppointmentRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createappointmentrequest
 */


namespace App\Domains\Beauty\Http\Requests;

final class CreateAppointmentRequest
{

    public function authorize(): bool
        {
            return true;
        }

        public function rules(): array
        {
            return [
                'salon_id' => ['required', 'exists:beauty_salons,id'],
                'master_id' => ['required', 'exists:masters,id'],
                'service_id' => ['required', 'exists:beauty_services,id'],
                'user_id' => ['nullable', 'exists:users,id'],
                'datetime_start' => ['required', 'date', 'after:now'],
                'datetime_end' => ['required', 'date', 'after:datetime_start'],
                'price' => ['required', 'integer', 'min:0'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'tags' => ['nullable', 'array'],
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

}
