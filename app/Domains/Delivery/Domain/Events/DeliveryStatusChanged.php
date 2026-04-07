<?php

declare(strict_types=1);

/**
 * DeliveryStatusChanged — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/deliverystatuschanged
 */


namespace App\Domains\Delivery\Domain\Events;

use App\Domains\Delivery\Domain\Entities\Delivery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class DeliveryStatusChanged
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Delivery\Domain\Events
 */
final class DeliveryStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Delivery $delivery,
        public readonly string $correlationId
    ) {

    }
}
