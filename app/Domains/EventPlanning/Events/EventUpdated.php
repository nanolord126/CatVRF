<?php declare(strict_types=1);

/**
 * EventUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/eventupdated
 */


namespace App\Domains\EventPlanning\Events;

use App\Domains\EventPlanning\Models\Event;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class EventUpdated
 *
 * Part of the EventPlanning vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\EventPlanning\Events
 */
final class EventUpdated
{

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Event $event,
        public readonly string $correlationId) {}
}
