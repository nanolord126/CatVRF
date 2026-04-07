<?php declare(strict_types=1);

/**
 * WeddingBookingUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/weddingbookingupdated
 */


namespace App\Domains\WeddingPlanning\Events;

use App\Domains\WeddingPlanning\Models\WeddingBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class WeddingBookingUpdated
 *
 * Part of the WeddingPlanning vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\WeddingPlanning\Events
 */
final class WeddingBookingUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly WeddingBooking $weddingBooking,
        private readonly string $correlationId) {}
}
