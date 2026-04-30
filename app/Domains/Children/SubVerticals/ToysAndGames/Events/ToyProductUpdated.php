<?php

declare(strict_types=1);

/**
 * ToyProductUpdated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/toyproductupdated
 */

namespace App\Domains\ToysAndGames\Events;

use App\Domains\ToysAndGames\Models\ToyProduct;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class ToyProductUpdated
 *
 * Part of the ToysAndGames vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class ToyProductUpdated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly ToyProduct $toyProduct,
        private readonly string $correlationId
    ) {}
}
