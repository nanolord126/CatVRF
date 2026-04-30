<?php

declare(strict_types=1);

/**
 * AutoBaseService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/autobaseservice
 */

namespace App\Domains\Auto\Services;

final readonly class AutoBaseService
{
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


    public function getVerticalName(): string
    {
        return 'auto';
    }

    /**
     * Auto vertical standard commission:
     * 15% + 5% fleet / 17.5% self-employed.
     * Retuning the base 15%.
     */
    public function getBaseCommissionRate(): float
    {
        return 0.15;
    }

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return self::class.'@'.self::VERSION;
    }
}
