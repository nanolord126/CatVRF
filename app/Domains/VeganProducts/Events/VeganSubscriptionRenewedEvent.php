<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;




/**
     * VeganSubscriptionRenewedEvent - Triggered after a subscription box is processed.
     */
final class VeganSubscriptionRenewedEvent
{
        use Dispatchable, SerializesModels;

        public function __construct(
            private readonly int $subscriptionId,
            private readonly int $boxId,
            private readonly string $correlationId) {}
}
