<?php

declare(strict_types=1);

/**
 * RentalBookingUpdated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/rentalbookingupdated
 */

namespace App\Domains\CarRental\Events;

use App\Domains\CarRental\Models\RentalBooking;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class RentalBookingUpdated
 *
 * Part of the CarRental vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class RentalBookingUpdated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly RentalBooking $rentalBooking,
        public readonly string $correlationId
    ) {}
}
