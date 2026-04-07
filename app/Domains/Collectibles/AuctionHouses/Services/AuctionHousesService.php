<?php declare(strict_types=1);

namespace App\Domains\Collectibles\AuctionHouses\Services;

use App\Domains\Collectibles\AuctionHouses\Models\Auction;
use App\Domains\Collectibles\AuctionHouses\Models\Bid;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class AuctionHousesService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Создание ставки на аукционе.
     */
    public function createBid(
        int $auctionId,
        int $bidAmount,
        string $correlationId = '',
    ): Bid {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: 0,
            operationType: 'auction_bid',
            amount: $bidAmount,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($auctionId, $bidAmount, $correlationId): Bid {
            $auction = Auction::findOrFail($auctionId);

            $bid = Bid::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $auction->tenant_id,
                'auction_id' => $auctionId,
                'bidder_id' => 0,
                'correlation_id' => $correlationId,
                'bid_amount' => $bidAmount,
                'payment_status' => 'pending',
                'tags' => ['auction' => true],
            ]);

            $auction->update(['current_bid' => $bidAmount]);

            $this->logger->info('Auction bid created', [
                'bid_id' => $bid->id,
                'auction_id' => $auctionId,
                'amount' => $bidAmount,
                'correlation_id' => $correlationId,
            ]);

            return $bid;
        });
    }

    /**
     * Завершение ставки — выплата продавцу.
     */
    public function completeBid(int $bidId, string $correlationId = ''): Bid
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($bidId, $correlationId): Bid {
            $bid = Bid::findOrFail($bidId);

            if ($bid->payment_status === 'completed') {
                throw new \RuntimeException('Already paid', 400);
            }

            $bid->update([
                'payment_status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $payout = (int) ($bid->bid_amount * 0.86);

            $this->wallet->credit(
                walletId: $bid->tenant_id,
                amount: $payout,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: [
                    'bid_id' => $bid->id,
                    'payout' => $payout,
                    'correlation_id' => $correlationId,
                ],
            );

            $this->logger->info('Auction bid completed', [
                'bid_id' => $bid->id,
                'payout' => $payout,
                'correlation_id' => $correlationId,
            ]);

            return $bid;
        });
    }

    /**
     * Отмена ставки.
     */
    public function cancelBid(int $bidId, string $correlationId = ''): Bid
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($bidId, $correlationId): Bid {
            $bid = Bid::findOrFail($bidId);

            if ($bid->payment_status === 'completed') {
                throw new \RuntimeException('Cannot cancel paid bid', 400);
            }

            $bid->delete();

            $this->logger->info('Auction bid cancelled', [
                'bid_id' => $bidId,
                'correlation_id' => $correlationId,
            ]);

            return $bid;
        });
    }

    /**
     * Получение ставки по ID.
     */
    public function getBid(int $bidId): Bid
    {
        return Bid::findOrFail($bidId);
    }

    /**
     * Получение всех ставок аукциона (топ-20).
     */
    public function getAuctionBids(int $auctionId): Collection
    {
        return Bid::query()
            ->where('auction_id', $auctionId)
            ->orderByDesc('bid_amount')
            ->take(20)
            ->get();
    }
}
