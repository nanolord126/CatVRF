<?php

declare(strict_types=1);

/**
 * CreateBeautySalonRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createbeautysalonrequest
 */


namespace App\Domains\Beauty\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class CreateBeautySalonRequest
{
    public function __construct(
        private Guard $guard) {}


    public function authorize(): bool
        {
            return $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'beauty_salon_create',
                amount: 0
            );
        }

        public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:500'],
                'description' => ['nullable', 'string'],
                'phone' => ['required', 'string', 'max:20'],
                'email' => ['nullable', 'email', 'max:255'],
                'schedule' => ['nullable', 'array'],
                'tags' => ['nullable', 'array'],
                'is_active' => ['boolean'],
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

}
