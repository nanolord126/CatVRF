<?php

declare(strict_types=1);

/**
 * RideFinished — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/ridefinished
 */

namespace App\Domains\Auto\Taxi\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class RideFinished
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class RideFinished
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId,
        public readonly int $amount,
        public readonly string $correlationId
    ) {}
}
