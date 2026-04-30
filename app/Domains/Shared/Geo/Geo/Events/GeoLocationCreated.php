<?php

declare(strict_types=1);

/**
 * GeoLocationCreated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/geolocationcreated
 */

namespace App\Domains\Geo\Events;

use App\Domains\Geo\Models\GeoLocation;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class GeoLocationCreated
 *
 * Part of the Geo vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class GeoLocationCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly GeoLocation $geoLocation,
        public readonly string $correlationId
    ) {}
}
