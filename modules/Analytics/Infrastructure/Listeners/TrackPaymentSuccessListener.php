<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Listeners;

use Modules\Analytics\Application\Facades\Analytics;

/**
 * Track Payment Success Listener
 *
 * Listens for payment success events and tracks them in analytics.
 */
final readonly class TrackPaymentSuccessListener
{
    public function handle(object $event): void
    {
        // TODO: Extract data from actual event
        // $tenantId = $event->tenantId;
        // $userId = $event->userId;
        // $paymentId = $event->paymentId;
        // $amount = $event->amount;
        
        // Analytics::trackPaymentSuccessful($tenantId, $userId, $paymentId, $amount);
    }
}
