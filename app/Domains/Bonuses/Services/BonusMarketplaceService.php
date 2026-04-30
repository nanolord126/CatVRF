<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\Interfaces\LockedBonusRepositoryInterface;
use App\Domains\Bonuses\Models\LockedBonusBatch;
use App\Domains\Bonuses\Models\BonusWallet;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * BonusMarketplaceService - Service for bonus marketplace operations
 * 
 * Handles selling locked bonuses with discount and buying from marketplace.
 * Platform earns 12% commission on marketplace sales.
 */
final readonly class BonusMarketplaceService
{
    private const PLATFORM_COMMISSION_RATE = 0.12; // 12%

    public function __construct(
        private readonly LockedBonusRepositoryInterface $repository,
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    public function sellLockedBonus(
        int $userId,
        int $tenantId,
        int $batchId,
        float $discount,
        ?string $correlationId = null,
    ): void {
        // Validate discount (15-40%)
        if ($discount < 15 || $discount > 40) {
            throw new \InvalidArgumentException("Discount must be between 15% and 40%");
        }

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'marketplace_sell',
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'batch_id' => $batchId,
            'discount' => $discount,
            'correlation_id' => $correlationId,
        ]);

        $batch = $this->repository->findById($batchId);

        if (!$batch) {
            throw new \InvalidArgumentException("Batch not found: {$batchId}");
        }

        if ($batch->user_id !== $userId) {
            throw new \InvalidArgumentException("Batch does not belong to user");
        }

        if ($batch->isFullyVested()) {
            throw new \InvalidArgumentException("Cannot sell fully vested batch");
        }

        $this->db->transaction(function () use ($batch, $discount, $correlationId) {
            $salePrice = $batch->remaining_locked * (1 - $discount / 100);
            $commission = $salePrice * self::PLATFORM_COMMISSION_RATE;
            $netAmount = $salePrice - $commission;

            // Credit net amount to user's bonus wallet
            $wallet = BonusWallet::getOrCreateForUser($batch->user_id);
            $wallet->creditAvailable((int) ($netAmount * 100)); // Convert to kopecks

            // Mark batch as sold (soft delete with metadata)
            $batch->delete();

            // Log audit
            $this->audit->record(
                'locked_bonus_marketplace_sale',
                LockedBonusBatch::class,
                $batch->id,
                [],
                [
                    'user_id' => $batch->user_id,
                    'tenant_id' => $batch->tenant_id,
                    'batch_id' => $batch->id,
                    'original_amount' => $batch->original_amount,
                    'remaining_locked' => $batch->remaining_locked,
                    'discount' => $discount,
                    'sale_price' => $salePrice,
                    'commission' => $commission,
                    'net_amount' => $netAmount,
                ],
                $correlationId,
            );

            $this->logger->info('Locked bonus sold on marketplace', [
                'batch_id' => $batch->id,
                'user_id' => $batch->user_id,
                'remaining_locked' => $batch->remaining_locked,
                'discount' => $discount,
                'sale_price' => $salePrice,
                'commission' => $commission,
                'net_amount' => $netAmount,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function buyLockedBonus(
        int $buyerUserId,
        int $tenantId,
        int $batchId,
        ?string $correlationId = null,
    ): void {
        // Fraud check
        $this->fraud->check([
            'operation_type' => 'marketplace_buy',
            'user_id' => $buyerUserId,
            'tenant_id' => $tenantId,
            'batch_id' => $batchId,
            'correlation_id' => $correlationId,
        ]);

        // Note: In a full implementation, this would query a marketplace listings table
        // For now, we'll throw an exception as marketplace listings need to be implemented
        throw new \RuntimeException("Marketplace buying requires marketplace listings implementation");
    }

    public function getCommissionRate(): float
    {
        return self::PLATFORM_COMMISSION_RATE;
    }

    public function calculateSalePrice(float $remainingLocked, float $discount): float
    {
        return $remainingLocked * (1 - $discount / 100);
    }

    public function calculateNetAmount(float salePrice): float
    {
        return $salePrice * (1 - self::PLATFORM_COMMISSION_RATE);
    }

    public function calculateCommission(float salePrice): float
    {
        return $salePrice * self::PLATFORM_COMMISSION_RATE;
    }
}
