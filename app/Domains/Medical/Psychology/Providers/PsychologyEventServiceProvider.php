<?php declare(strict_types=1);

/**
 * PsychologyEventServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/psychologyeventserviceprovider
 */


namespace App\Domains\Medical\Psychology\Providers;

use Illuminate\Support\ServiceProvider;

final class PsychologyEventServiceProvider extends ServiceProvider
{

    protected $listen = [
            PsychologicalBookingCreated::class => [
                HandlePsychologicalBookingCreated::class,
            ],
        ];

        public function boot(): void
        {
            parent::boot();
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
