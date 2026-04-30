<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Bid Placed Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class BidPlaced
{
    use Dispatchable;

    public function __construct(
        public readonly int $bidId,
        public readonly int $auctionId,
        public readonly int $tenantId,
        public readonly int $bidderId,
        public readonly int $amount,
        public readonly string $correlationId,
    ) {}
}
