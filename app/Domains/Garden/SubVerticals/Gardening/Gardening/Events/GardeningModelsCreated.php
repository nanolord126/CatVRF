<?php

declare(strict_types=1);

/**
 * GardeningModelsCreated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/gardeningmodelscreated
 */

namespace App\Domains\Garden\SubVerticals\Gardening\Events;

use App\Domains\Gardening\Models\GardeningModels;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class GardeningModelsCreated
 *
 * Part of the Gardening vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class GardeningModelsCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly GardeningModels $gardeningModels,
        public readonly string $correlationId
    ) {}
}
