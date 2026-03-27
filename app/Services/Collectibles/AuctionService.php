<?php

declare(strict_types=1);

namespace App\Services\Collectibles;

use App\Models\Collectibles\CollectibleAuction;
use App\Models\Collectibles\CollectibleItem;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AuctionService — Real-time bidding engine for collectibles.
 * Implements reserve pricing, anti-fraud bidding, and settlement.
 */
final readonly class AuctionService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private string $correlationId = ''
    ) {
        $this->correlationId = $correlationId ?: (string) Str::uuid();
    }

    /**
     * Place a new bid on an active auction.
     * @throws \Exception
     */
    public function placeBid(int $userId, int $auctionId, int $amountCents): CollectibleAuction
    {
        $auction = CollectibleAuction::with('item.store')->lockForUpdate()->findOrFail($auctionId);

        // 1. Validation Logic
        if ($auction->status !== 'active') {
            throw new \Exception('Auction is not active.');
        }

        if ($auction->ends_at->isPast()) {
            throw new \Exception('Auction has ended.');
        }

        if ($amountCents <= $auction->current_bid_cents) {
            throw new \Exception('Bid must be higher than current highest bid.');
        }

        // 2. Anti-shill / Anti-fraud check
        $this->fraud->check([
            'operation' => 'place_bid',
            'user_id' => $userId,
            'auction_id' => $auctionId,
            'amount' => $amountCents,
            'correlation_id' => $this->correlationId,
        ]);

        return DB::transaction(function () use ($userId, $auction, $amountCents) {
            // 3. Bid recording
            $auction->update([
                'current_bid_cents' => $amountCents,
                'last_bidder_id' => $userId,
                'correlation_id' => $this->correlationId,
            ]);

            Log::channel('audit')->info('New auction bid placed', [
                'auction_id' => $auction->id,
                'user_id' => $userId,
                'amount' => $amountCents,
                'correlation_id' => $this->correlationId,
            ]);

            return $auction;
        });
    }

    /**
     * Finalize the auction after expiry.
     */
    public function finalizeAuction(int $auctionId): void
    {
        $auction = CollectibleAuction::with('item.store')->findOrFail($auctionId);

        if ($auction->status !== 'active') {
            return;
        }

        DB::transaction(function () use ($auction) {
            if ($auction->last_bidder_id && $auction->reserveMet()) {
                // Success: Settle transaction
                $this->wallet->debit($auction->last_bidder_id, $auction->current_bid_cents, "Win auction: {$auction->item->name}");
                
                $commission = (int) ($auction->current_bid_cents * 0.14);
                $this->wallet->credit($auction->item->store->tenant_id, $auction->current_bid_cents - $commission, "Auction win: {$auction->item->name}");

                $auction->update(['status' => 'completed']);
                $auction->item->update(['collection_id' => $this->getOrCreateUserCollection($auction->last_bidder_id, $auction->item->category->name)]);
            } else {
                // Failure: No bids or reserve not met
                $auction->update(['status' => 'cancelled']);
            }

            Log::channel('audit')->info('Auction finalized', [
                'auction_id' => $auction->id,
                'status' => $auction->status,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }

    private function getOrCreateUserCollection(int $userId, string $categoryName): int
    {
        $collection = \App\Models\Collectibles\UserCollection::firstOrCreate([
            'user_id' => $userId,
            'name' => "My {$categoryName} Collection",
        ]);

        return $collection->id;
    }
}
