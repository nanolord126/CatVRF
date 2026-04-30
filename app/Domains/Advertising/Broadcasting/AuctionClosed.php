<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Auction Closed Broadcast Event
 *
 * Real-time notification when an auction is closed.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class AuctionClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $auctionId,
        public readonly string $auctionUuid,
        public readonly ?int $winningBidId,
        public readonly ?int $winningAmount,
        public readonly ?int $winningBidderId,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('auctions.' . $this->auctionUuid);
    }

    public function broadcastAs(): string
    {
        return 'auction.closed';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auctionId,
            'auction_uuid' => $this->auctionUuid,
            'winning_bid_id' => $this->winningBidId,
            'winning_amount' => $this->winningAmount,
            'winning_bidder_id' => $this->winningBidderId,
            'correlation_id' => $this->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
