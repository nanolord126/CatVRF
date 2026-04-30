<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Auction Price Updated Broadcast Event
 *
 * Real-time notification when the current price changes in an auction.
 * Used for Dutch auctions and live price updates.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class AuctionPriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $auctionId,
        public readonly string $auctionUuid,
        public readonly int $currentPrice,
        public readonly int $previousPrice,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('auctions.' . $this->auctionUuid);
    }

    public function broadcastAs(): string
    {
        return 'price.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auctionId,
            'auction_uuid' => $this->auctionUuid,
            'current_price' => $this->currentPrice,
            'previous_price' => $this->previousPrice,
            'price_change' => $this->currentPrice - $this->previousPrice,
            'correlation_id' => $this->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
