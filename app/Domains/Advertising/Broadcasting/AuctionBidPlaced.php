<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Auction Bid Placed Broadcast Event
 *
 * Real-time notification when a bid is placed in an auction.
 * Broadcasts to all listeners for the specific auction.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class AuctionBidPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $auctionId,
        public readonly string $auctionUuid,
        public readonly int $bidId,
        public readonly string $bidUuid,
        public readonly int $bidderId,
        public readonly int $amount,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('auctions.' . $this->auctionUuid);
    }

    public function broadcastAs(): string
    {
        return 'bid.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auctionId,
            'auction_uuid' => $this->auctionUuid,
            'bid_id' => $this->bidId,
            'bid_uuid' => $this->bidUuid,
            'bidder_id' => $this->bidderId,
            'amount' => $this->amount,
            'correlation_id' => $this->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
