<?php

declare(strict_types=1);

/**
 * AdCampaignCreated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/adcampaigncreated
 */

namespace App\Domains\Advertising\Events;

use App\Domains\Advertising\Models\AdCampaign;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class AdCampaignCreated
 *
 * Part of the Advertising vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class AdCampaignCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly AdCampaign $adCampaign,
        public readonly string $correlationId,
    ) {}
}
