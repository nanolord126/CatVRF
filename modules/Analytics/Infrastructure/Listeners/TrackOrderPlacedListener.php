<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Listeners;

use Modules\Analytics\Application\Facades\Analytics;

/**
 * Track Order Placed Listener
 *
 * Listens for order placement events and tracks them in analytics.
 * This is an infrastructure listener - bridges domain events to analytics.
 * 
 * TODO: Define the actual OrderPlaced event class in the appropriate domain.
 */
final readonly class TrackOrderPlacedListener
{
    public function handle(object $event): void
    {
        // TODO: Extract data from actual event
        // $tenantId = $event->tenantId;
        // $userId = $event->userId;
        // $orderId = $event->orderId;
        // $revenue = $event->total;
        
        // Analytics::trackOrderPlaced($tenantId, $userId, $orderId, $revenue);
    }
}
