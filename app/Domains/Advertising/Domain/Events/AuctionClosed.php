<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Auction Closed Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class AuctionClosed
{
    use Dispatchable;

    public function __construct(
        public readonly int $auctionId,
        public readonly int $winningBidId,
        public readonly int $finalPrice,
        public readonly string $correlationId,
    ) {}
}
