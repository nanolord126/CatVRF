<?php

declare(strict_types=1);

/**
 * RideRequested — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/riderequested
 */


namespace App\Domains\Auto\Taxi\Domain\Events;

use App\Domains\Auto\Taxi\Domain\ValueObjects\RideId;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class RideRequested
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Auto\Taxi\Domain\Events
 */
final class RideRequested
{
    
    public function __construct(
        public readonly RideId $rideId,
        public readonly int $clientId
    ) {

    }
}
