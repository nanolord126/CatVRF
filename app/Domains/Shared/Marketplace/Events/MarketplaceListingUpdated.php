<?php

declare(strict_types=1);

/**
 * MarketplaceListingUpdated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/marketplacelistingupdated
 */

namespace App\Domains\Marketplace\Events;

use App\Domains\Marketplace\Models\MarketplaceListing;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class MarketplaceListingUpdated
 *
 * Part of the Marketplace vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class MarketplaceListingUpdated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly MarketplaceListing $marketplaceListing,
        private readonly string $correlationId
    ) {}
}
