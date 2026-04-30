<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Domains\Advertising\Domain\Entities\Auction;
use App\Domains\Advertising\Domain\Entities\Bid;
use App\Domains\Advertising\Domain\Interfaces\AuctionRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\BidRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Auction Service
 *
 * Handles auction lifecycle, bid processing, and winner selection.
 * Supports forward, dutch, and sealed-bid auction types.
 * Uses Redis for atomic bid processing and state management.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class AuctionService
{
    private const AUCTION_STATE_TTL = 86400; // 24 hours
    private const BID_LOCK_TTL = 5; // 5 seconds

    public function __construct(
        private readonly AuctionRepositoryInterface $auctionRepository,
        private readonly BidRepositoryInterface $bidRepository,
        private readonly FraudControlService $fraudService,
        private readonly Dispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Place a bid on an auction
     *
     * @throws \RuntimeException
     */
    public function placeBid(
        int $auctionId,
        int $tenantId,
        int $bidderId,
        int $amount,
        int $userId = 0,
        ?string $correlationId = null,
    ): Bid {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('Placing bid', [
            'correlation_id' => $correlationId,
            'auction_id' => $auctionId,
            'bidder_id' => $bidderId,
            'amount' => $amount,
        ]);

        // Fraud check
        $this->fraudService->check(
            userId: $userId,
            operationType: 'auction_bid',
            amount: $amount,
            correlationId: $correlationId,
            context: ['auction_id' => $auctionId, 'bidder_id' => $bidderId],
        );

        // Get auction with Redis lock
        $lockKey = "auction:{$auctionId}:lock";
        $lock = Redis::lock($lockKey, self::BID_LOCK_TTL);

        try {
            $lock->block(5);

            $auction = $this->auctionRepository->findById($auctionId);
            if ($auction === null) {
                throw new \RuntimeException('Auction not found');
            }

            if (!$auction->isActive()) {
                throw new \RuntimeException('Auction is not active');
            }

            if (!$auction->canBid($amount)) {
                throw new \RuntimeException('Bid amount is invalid');
            }

            // For dutch auctions, price decreases over time
            if ($auction->type === 'dutch') {
                $elapsedTime = now()->diffInSeconds($auction->start_at);
                $priceDecay = (int) ($elapsedTime * 100); // 100 kopeks per second
                $auction = $this->updateDutchPrice($auction, $priceDecay);
            }

            return $this->db->transaction(function () use (
                $auction,
                $tenantId,
                $bidderId,
                $amount,
                $correlationId,
            ) {
                // Create bid
                $bid = Bid::place(
                    auctionId: $auction->id,
                    tenantId: $tenantId,
                    bidderId: $bidderId,
                    amount: $amount,
                    correlationId: $correlationId,
                );

                $savedBid = $this->bidRepository->save($bid);

                // Update auction bid history
                $bidHistory = $auction->bid_history;
                $bidHistory[] = [
                    'bid_id' => $savedBid->uuid,
                    'bidder_id' => $bidderId,
                    'amount' => $amount,
                    'placed_at' => $savedBid->placed_at->toIso8601String(),
                ];

                $this->auctionRepository->updateBidHistory($auction->id, $bidHistory);

                // Update current price for forward auctions
                if ($auction->type === 'forward' && $amount > $auction->current_price) {
                    $this->auctionRepository->updateCurrentPrice($auction->id, $amount);
                }

                // For dutch auctions, first bid wins
                if ($auction->type === 'dutch') {
                    $this->closeAuction($auction->id, $savedBid->id);
                }

                // Dispatch event
                $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\BidPlaced(
                    bidId: $savedBid->id,
                    auctionId: $auction->id,
                    tenantId: $tenantId,
                    bidderId: $bidderId,
                    amount: $amount,
                    correlationId: $correlationId,
                ));

                $this->logger->info('Bid placed successfully', [
                    'correlation_id' => $correlationId,
                    'bid_id' => $savedBid->id,
                    'auction_id' => $auction->id,
                ]);

                return $savedBid;
            });
        } finally {
            $lock?->release();
        }
    }

    /**
     * Close an auction and determine winner
     *
     * @throws \RuntimeException
     */
    public function closeAuction(int $auctionId, ?int $winningBidId = null): void
    {
        $this->logger->info('Closing auction', [
            'auction_id' => $auctionId,
            'winning_bid_id' => $winningBidId,
        ]);

        $auction = $this->auctionRepository->findById($auctionId);
        if ($auction === null) {
            throw new \RuntimeException('Auction not found');
        }

        if (!$auction->canClose()) {
            throw new \RuntimeException('Auction cannot be closed');
        }

        $this->db->transaction(function () use ($auction, $winningBidId) {
            $bids = $this->bidRepository->findByAuction($auction->id);

            $winningBid = null;

            if ($winningBidId !== null) {
                $winningBid = $this->bidRepository->findById($winningBidId);
            } else {
                // Determine winner based on auction type
                $winningBid = match ($auction->type) {
                    'forward', 'dutch' => $this->bidRepository->findHighest($auction->id),
                    'sealed_bid' => $this->determineSealedBidWinner($auction, $bids),
                    default => null,
                };
            }

            // Update bid statuses
            foreach ($bids as $bid) {
                if ($winningBid !== null && $bid->id === $winningBid->id) {
                    $this->bidRepository->updateStatus($bid->id, 'winning');
                } else {
                    $this->bidRepository->updateStatus($bid->id, 'losing');
                }
            }

            // Update auction status
            $this->auctionRepository->updateStatus($auction->id, 'closed');

            // Dispatch events
            if ($winningBid !== null) {
                $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\BidWon(
                    bidId: $winningBid->id,
                    auctionId: $auction->id,
                    tenantId: $winningBid->tenant_id,
                    bidderId: $winningBid->bidder_id,
                    amount: $winningBid->amount,
                    correlationId: $auction->correlation_id,
                ));
            }

            $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\AuctionClosed(
                auctionId: $auction->id,
                winningBidId: $winningBid?->id ?? 0,
                finalPrice: $winningBid?->amount ?? $auction->current_price,
                correlationId: $auction->correlation_id,
            ));

            $this->logger->info('Auction closed successfully', [
                'auction_id' => $auction->id,
                'winning_bid_id' => $winningBid?->id,
            ]);
        });
    }

    /**
     * Start an auction
     *
     * @throws \RuntimeException
     */
    public function startAuction(int $auctionId): void
    {
        $auction = $this->auctionRepository->findById($auctionId);
        if ($auction === null) {
            throw new \RuntimeException('Auction not found');
        }

        if (!$auction->canStart()) {
            throw new \RuntimeException('Auction cannot be started');
        }

        $this->auctionRepository->updateStatus($auctionId, 'active');

        // Cache auction state in Redis
        Redis::setex(
            "auction:{$auctionId}:state",
            self::AUCTION_STATE_TTL,
            json_encode([
                'auction_id' => $auction->id,
                'status' => 'active',
                'current_price' => $auction->current_price,
                'reserve_price' => $auction->reserve_price,
            ])
        );

        $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\AuctionStarted(
            auctionId: $auction->id,
            tenantId: $auction->tenant_id,
            correlationId: $auction->correlation_id,
        ));
    }

    /**
     * Get current auction state from Redis or database
     */
    public function getAuctionState(int $auctionId): ?array
    {
        $cached = Redis::get("auction:{$auctionId}:state");

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $auction = $this->auctionRepository->findById($auctionId);
        if ($auction === null) {
            return null;
        }

        return [
            'auction_id' => $auction->id,
            'status' => $auction->status,
            'current_price' => $auction->current_price,
            'reserve_price' => $auction->reserve_price,
            'bid_count' => count($auction->bid_history),
        ];
    }

    /**
     * Determine winner for sealed-bid auction (VCG mechanism)
     */
    private function determineSealedBidWinner(Auction $auction, iterable $bids): ?Bid
    {
        if (iterator_count($bids) === 0) {
            return null;
        }

        // Sort bids by amount descending
        $sortedBids = iterator_to_array($bids);
        usort($sortedBids, fn (Bid $a, Bid $b) => $b->amount <=> $a->amount);

        $highestBid = $sortedBids[0];

        // VCG pricing: winner pays second-highest bid
        if (count($sortedBids) > 1) {
            $secondHighest = $sortedBids[1];
            $this->auctionRepository->updateCurrentPrice($auction->id, $secondHighest->amount);
        }

        return $highestBid;
    }

    /**
     * Update Dutch auction price based on elapsed time
     */
    private function updateDutchPrice(Auction $auction, int $priceDecay): Auction
    {
        $newPrice = max($auction->reserve_price, $auction->starting_price - $priceDecay);

        if ($newPrice !== $auction->current_price) {
            $this->auctionRepository->updateCurrentPrice($auction->id, $newPrice);
            $auction = $this->auctionRepository->findById($auction->id) ?? $auction;
        }

        return $auction;
    }

    /**
     * Withdraw a bid
     *
     * @throws \RuntimeException
     */
    public function withdrawBid(int $bidId, int $bidderId): void
    {
        $bid = $this->bidRepository->findById($bidId);
        if ($bid === null) {
            throw new \RuntimeException('Bid not found');
        }

        if ($bid->bidder_id !== $bidderId) {
            throw new \RuntimeException('Unauthorized to withdraw this bid');
        }

        if ($bid->status !== 'pending') {
            throw new \RuntimeException('Cannot withdraw bid in current status');
        }

        $this->bidRepository->updateStatus($bidId, 'withdrawn');

        $this->logger->info('Bid withdrawn', [
            'bid_id' => $bidId,
            'bidder_id' => $bidderId,
        ]);
    }
}
