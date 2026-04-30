<?php

declare(strict_types=1);

/**
 * DeliveryOrderCreated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/deliveryordercreated
 */

namespace App\Domains\Delivery\Events;

use App\Domains\Delivery\Models\DeliveryOrder;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class DeliveryOrderCreated
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class DeliveryOrderCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly DeliveryOrder $deliveryOrder,
        public readonly string $correlationId
    ) {}
}
