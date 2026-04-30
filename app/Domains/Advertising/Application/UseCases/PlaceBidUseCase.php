<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Application\UseCases;

use App\Domains\Advertising\Domain\Entities\Auction;
use App\Domains\Advertising\Domain\Entities\Bid;
use App\Domains\Advertising\Domain\Interfaces\AuctionRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\BidRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use App\Traits\WithAuditLogging;

/**
 * Place Bid Use Case
 *
 * Handles bid placement in auctions.
 * Includes fraud checks, validation, and real-time bid processing.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class PlaceBidUseCase
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuctionRepositoryInterface $auctionRepository,
        private readonly BidRepositoryInterface $bidRepository,
        private readonly FraudControlService $fraudService,
        private readonly Dispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(
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
            'tenant_id' => $tenantId,
            'bidder_id' => $bidderId,
            'amount' => $amount,
        ]);

        // Validate bid amount
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Bid amount must be positive');
        }

        // Load auction
        $auction = $this->auctionRepository->findById($auctionId);

        if ($auction === null) {
            throw new \InvalidArgumentException('Auction not found');
        }

        // Verify tenant ownership
        if ($auction->tenant_id !== $tenantId) {
            throw new \InvalidArgumentException('Access denied');
        }

        // Check if auction is active
        if (!$auction->isActive()) {
            throw new \InvalidArgumentException('Auction is not active');
        }

        // Check if bid is valid
        if (!$auction->canBid($amount)) {
            throw new \InvalidArgumentException('Bid amount is too low');
        }

        // Fraud check
        try {
            $fraudResult = $this->fraudService->check(
                userId: $userId,
                operationType: 'place_bid',
                amount: $amount,
                ipAddress: request()?->ip(),
                deviceFingerprint: request()?->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
                context: [
                    'auction_id' => $auctionId,
                    'bidder_id' => $bidderId,
                ],
            );

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Bid blocked by security check');
            }
        } catch (\Throwable $e) {
            $this->logger->error('Fraud check failed for bid placement', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $this->db->transaction(function () use (
            $auction,
            $tenantId,
            $bidderId,
            $amount,
            $userId,
            $correlationId,
        ) {
            // Create bid entity
            $bid = Bid::place(
                auctionId: $auction->id,
                tenantId: $tenantId,
                bidderId: $bidderId,
                amount: $amount,
                correlationId: $correlationId,
            );

            // Save bid
            $savedBid = $this->bidRepository->save($bid);

            // Update auction current price
            $updatedAuction = new Auction(
                id: $auction->id,
                uuid: $auction->uuid,
                tenant_id: $auction->tenant_id,
                name: $auction->name,
                type: $auction->type,
                status: $auction->status,
                start_at: $auction->start_at,
                end_at: $auction->end_at,
                starting_price: $auction->starting_price,
                current_price: $amount, // Update to new bid amount
                reserve_price: $auction->reserve_price,
                inventory_id: $auction->inventory_id,
                bid_history: array_merge($auction->bid_history, [
                    [
                        'bid_id' => $savedBid->uuid,
                        'bidder_id' => $bidderId,
                        'amount' => $amount,
                        'placed_at' => \Carbon\Carbon::now()->toIso8601String(),
                    ],
                ]),
                correlation_id: $auction->correlation_id,
            );

            $this->auctionRepository->save($updatedAuction);

            // Audit logging
            $this->logAction(
                action: 'place_bid',
                entityType: 'auction',
                entityId: $auction->id,
                context: [
                    'bid_id' => $savedBid->uuid,
                    'bidder_id' => $bidderId,
                    'amount' => $amount,
                    'correlation_id' => $correlationId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

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
                'amount' => $amount,
            ]);

            return $savedBid;
        });
    }
}
