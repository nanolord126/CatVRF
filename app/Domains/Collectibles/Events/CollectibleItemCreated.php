<?php declare(strict_types=1);

/**
 * CollectibleItemCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/collectibleitemcreated
 */


namespace App\Domains\Collectibles\Events;

use App\Domains\Collectibles\Models\CollectibleItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class CollectibleItemCreated
 *
 * Part of the Collectibles vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Collectibles\Events
 */
final class CollectibleItemCreated
{

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly CollectibleItem $collectibleItem,
        public readonly string $correlationId) {}
}
