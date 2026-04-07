<?php declare(strict_types=1);

namespace App\Services\Collectibles;


use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Models\Collectibles\CollectibleAuction;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class AuctionService
{

    public function __construct(
        private readonly Request $request,
            private FraudControlService $fraud,
            private WalletService $wallet,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

        private function correlationId(): string
        {
            return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
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
                throw new \DomainException('Auction is not active.');
            }

            if ($auction->ends_at->isPast()) {
                throw new \DomainException('Auction has ended.');
            }

            if ($amountCents <= $auction->current_bid_cents) {
                throw new \DomainException('Bid must be higher than current highest bid.');
            }

            // 2. Anti-shill / Anti-fraud check
            $this->fraud->check((int) $userId, 'place_bid', $this->request->ip());

            return $this->db->transaction(function () use ($userId, $auction, $amountCents) {
                // 3. Bid recording
                $auction->update([
                    'current_bid_cents' => $amountCents,
                    'last_bidder_id' => $userId,
                    'correlation_id' => $this->correlationId(),
                ]);

                $this->logger->channel('audit')->info('New auction bid placed', [
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

            $this->db->transaction(function () use ($auction) {
                if ($auction->last_bidder_id && $auction->reserveMet()) {
                    // Success: Settle transaction
                    $this->wallet->debit($auction->last_bidder_id, $auction->current_bid_cents, \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $this->correlationId(), null, null, ['auction_id' => $auction->id]);

                    $commission = (int) ($auction->current_bid_cents * 0.14);
                    $this->wallet->credit($auction->item->store->tenant_id, $auction->current_bid_cents - $commission, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $this->correlationId(), null, null, [
                        'auction_id' => $auction->id,
                    ]);

                    $auction->update(['status' => 'completed', 'correlation_id' => $this->correlationId()]);
                } else {
                    $auction->update(['status' => 'expired', 'correlation_id' => $this->correlationId()]);
                }
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
