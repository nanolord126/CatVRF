<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Listeners;

use Modules\Analytics\Application\Facades\Analytics;

/**
 * Track Product Viewed Listener
 *
 * Listens for product view events and tracks them in analytics.
 */
final readonly class TrackProductViewedListener
{
    public function handle(object $event): void
    {
        // TODO: Extract data from actual event
        // $tenantId = $event->tenantId;
        // $userId = $event->userId;
        // $productId = $event->productId;
        // $sellerId = $event->sellerId;
        
        // Analytics::trackProductView($tenantId, $userId, $productId, $sellerId);
    }
}
