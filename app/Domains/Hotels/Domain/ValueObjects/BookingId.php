<?php

declare(strict_types=1);

/**
 * BookingId — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/bookingid
 */


namespace App\Domains\Hotels\Domain\ValueObjects;

use App\Shared\Domain\ValueObjects\UuidValueObject;

/**
 * Class BookingId
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Hotels\Domain\ValueObjects
 */
final class BookingId extends UuidValueObject
{
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
