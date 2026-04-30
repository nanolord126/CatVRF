<?php

declare(strict_types=1);

/**
 * CleaningOrderUpdated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/cleaningorderupdated
 */

namespace App\Domains\HomeServices\SubVerticals\CleaningServices\Events;

use App\Domains\CleaningServices\Models\CleaningOrder;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class CleaningOrderUpdated
 *
 * Part of the CleaningServices vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class CleaningOrderUpdated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly CleaningOrder $cleaningOrder,
        public readonly string $correlationId
    ) {}
}
