<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Auction Started Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class AuctionStarted
{
    use Dispatchable;

    public function __construct(
        public readonly int $auctionId,
        public readonly int $tenantId,
        public readonly string $correlationId,
    ) {}
}
