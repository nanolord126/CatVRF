<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Bid Won Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class BidWon
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
