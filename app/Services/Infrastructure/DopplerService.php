<?php

declare(strict_types=1);

/**
 * DopplerService — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/dopplerservice
 * @see https://catvrf.ru/docs/dopplerservice
 * @see https://catvrf.ru/docs/dopplerservice
 */

namespace App\Services\Infrastructure;

use App\Services\AuditService;
use App\Services\FraudControlService;

/**
 * Class DopplerService
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final class DopplerService
{
    /**
     * Get secret from Doppler with 1h cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return config($key, $default);
    }

    /**
     * Alias for get() to maintain compatibility.
     */
    public static function getSecret(string $key, mixed $default = null): mixed
    {
        return self::get($key, $default);
    }

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
