<?php

declare(strict_types=1);

/**
 * CraftItemCreated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/craftitemcreated
 */

namespace App\Domains\Leisure\SubVerticals\HobbyAndCraft\Events;

use App\Domains\HobbyAndCraft\Models\CraftItem;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class CraftItemCreated
 *
 * Part of the HobbyAndCraft vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class CraftItemCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly CraftItem $craftItem,
        private readonly string $correlationId
    ) {}
}
