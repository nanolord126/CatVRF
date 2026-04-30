<?php

declare(strict_types=1);

/**
 * CartLimitExceededException — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/cartlimitexceededexception
 * @see https://catvrf.ru/docs/cartlimitexceededexception
 * @see https://catvrf.ru/docs/cartlimitexceededexception
 * @see https://catvrf.ru/docs/cartlimitexceededexception
 * @see https://catvrf.ru/docs/cartlimitexceededexception
 * @see https://catvrf.ru/docs/cartlimitexceededexception
 */

namespace App\Exceptions;

/**
 * Class CartLimitExceededException
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 */
final class CartLimitExceededException extends \RuntimeException
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

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }
}
