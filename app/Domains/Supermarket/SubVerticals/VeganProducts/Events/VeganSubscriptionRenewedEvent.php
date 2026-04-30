<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VeganSubscriptionRenewedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $boxId,
        public readonly string $correlationId = '',
    ) {}
}
