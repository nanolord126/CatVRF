<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\UuidInterface;
use Modules\Payment\Domain\Entities\EscrowHold;
use Modules\Payment\Domain\Entities\PaymentIntent;
use Modules\Payment\Domain\Interfaces\WalletServiceInterface;
use Modules\Payment\Domain\Repositories\EscrowHoldRepositoryInterface;
use Modules\Payment\Domain\ValueObjects\Money;
use Psr\Log\LoggerInterface;

/**
 * Escrow Service - manages escrow holds for marketplace payments.
 */
final readonly class EscrowService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly WalletServiceInterface $walletService,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
        private readonly EscrowHoldRepositoryInterface $escrowRepository,
        private readonly UuidInterface $uuid,
    ) {}

    public function createHold(
        PaymentIntent $paymentIntent,
        int $walletId,
        Money $amount,
        array $releaseConditions = [],
        ?\DateTime $autoReleaseAt = null,
        ?string $correlationId = null,
    ): EscrowHold {
        $correlationId ??= $this->uuid->toString();

        $this->logger->info('Creating escrow hold', [
            'payment_intent_id' => $paymentIntent->id,
            'wallet_id' => $walletId,
            'amount_kopecks' => $amount->toKopecks(),
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use (
            $paymentIntent,
            $walletId,
            $amount,
            $releaseConditions,
            $autoReleaseAt,
            $correlationId,
        ) {
            // Hold funds in wallet
            $this->walletService->hold(
                walletId: $walletId,
                amount: $amount->toKopecks(),
                correlationId: $correlationId,
                sourceType: 'escrow',
                sourceId: $paymentIntent->id,
            );

            // Create escrow hold record
            $hold = $this->escrowRepository->create([
                'uuid' => $this->uuid->toString(),
                'tenant_id' => $paymentIntent->tenantId,
                'business_group_id' => $paymentIntent->businessGroupId,
                'payment_intent_id' => $paymentIntent->id,
                'wallet_id' => $walletId,
                'amount_kopecks' => $amount->toKopecks(),
                'currency' => $amount->currency,
                'status' => 'held',
                'release_conditions' => $releaseConditions,
                'auto_release_at' => $autoReleaseAt ? CarbonImmutable::instance($autoReleaseAt) : null,
                'remaining_amount_kopecks' => $amount->toKopecks(),
                'correlation_id' => $correlationId,
                'metadata' => [
                    'payment_intent_uuid' => $paymentIntent->uuid,
                ],
            ]);

            $this->audit->log(
                action: 'escrow_hold_created',
                subjectType: EscrowHold::class,
                subjectId: $hold->id,
                newValues: [
                    'payment_intent_id' => $paymentIntent->id,
                    'wallet_id' => $walletId,
                    'amount_kopecks' => $amount->toKopecks(),
                    'release_conditions' => $releaseConditions,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Escrow hold created', [
                'hold_id' => $hold->id,
                'uuid' => $hold->uuid,
                'correlation_id' => $correlationId,
            ]);

            return $hold;
        });
    }

    public function release(
        EscrowHold $hold,
        int $amountKopecks,
        int $targetWalletId,
        string $reason = 'Conditions met',
        ?string $correlationId = null,
    ): EscrowHold {
        $correlationId ??= $this->uuid->toString();

        if (!$hold->canRelease($amountKopecks)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot release %d kopecks from hold with %d remaining',
                    $amountKopecks,
                    $hold->remainingAmount->toKopecks()
                )
            );
        }

        $this->logger->info('Releasing escrow funds', [
            'hold_id' => $hold->id,
            'amount_kopecks' => $amountKopecks,
            'target_wallet_id' => $targetWalletId,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use (
            $hold,
            $amountKopecks,
            $targetWalletId,
            $reason,
            $correlationId,
        ) {
            // Release hold from source wallet
            $this->walletService->credit(
                walletId: $hold->walletId,
                amount: $amountKopecks,
                type: 'RELEASE_HOLD',
                correlationId: $correlationId,
                sourceType: 'escrow',
                sourceId: $hold->id,
            );

            // Credit target wallet
            $this->walletService->credit(
                walletId: $targetWalletId,
                amount: $amountKopecks,
                type: 'DEPOSIT',
                correlationId: $correlationId,
                sourceType: 'escrow',
                sourceId: $hold->id,
            );

            // Update hold record
            $newReleased = $hold->releasedAmount->toKopecks() + $amountKopecks;
            $newRemaining = $hold->remainingAmount->toKopecks() - $amountKopecks;

            $updated = $this->escrowRepository->update($hold->id, [
                'released_amount_kopecks' => $newReleased,
                'remaining_amount_kopecks' => $newRemaining,
                'status' => $newRemaining === 0 ? 'released' : 'partially_released',
                'release_reason' => $reason,
                'released_at' => $newRemaining === 0 ? now() : $hold->releasedAt,
            ]);

            $this->audit->log(
                action: $newRemaining === 0 ? 'escrow_fully_released' : 'escrow_partially_released',
                subjectType: EscrowHold::class,
                subjectId: $hold->id,
                newValues: [
                    'released_amount_kopecks' => $newReleased,
                    'remaining_amount_kopecks' => $newRemaining,
                    'reason' => $reason,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Escrow funds released', [
                'hold_id' => $hold->id,
                'released_amount' => $newReleased,
                'remaining_amount' => $newRemaining,
                'status' => $updated->status,
                'correlation_id' => $correlationId,
            ]);

            return $updated;
        });
    }

    public function cancel(
        EscrowHold $hold,
        string $reason = 'Canceled',
        ?string $correlationId = null,
    ): EscrowHold {
        $correlationId ??= $this->uuid->toString();

        if ($hold->status === 'released' || $hold->status === 'canceled') {
            throw new \InvalidArgumentException(
                sprintf('Cannot cancel hold in status: %s', $hold->status)
            );
        }

        $this->logger->info('Canceling escrow hold', [
            'hold_id' => $hold->id,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($hold, $reason, $correlationId) {
            // Release hold from wallet (return to available balance)
            $this->walletService->credit(
                walletId: $hold->walletId,
                amount: $hold->remainingAmount->toKopecks(),
                type: 'RELEASE_HOLD',
                correlationId: $correlationId,
                sourceType: 'escrow_cancel',
                sourceId: $hold->id,
            );

            // Update hold record
            $updated = $this->escrowRepository->update($hold->id, [
                'status' => 'canceled',
                'released_amount_kopecks' => $hold->amount->toKopecks(),
                'remaining_amount_kopecks' => 0,
                'release_reason' => $reason,
                'canceled_at' => now(),
            ]);

            $this->audit->log(
                action: 'escrow_hold_canceled',
                subjectType: EscrowHold::class,
                subjectId: $hold->id,
                newValues: [
                    'reason' => $reason,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Escrow hold canceled', [
                'hold_id' => $hold->id,
                'correlation_id' => $correlationId,
            ]);

            return $updated;
        });
    }

    public function processExpiredHolds(): int
    {
        $expiredHolds = $this->escrowRepository->findExpired();
        $processed = 0;

        foreach ($expiredHolds as $hold) {
            try {
                $this->cancel(
                    $hold,
                    reason: 'Auto-released due to expiration',
                    correlationId: $this->uuid->toString(),
                );
                $processed++;
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process expired escrow hold', [
                    'hold_id' => $hold->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Processed expired escrow holds', [
            'count' => $processed,
        ]);

        return $processed;
    }

    public function getActiveHolds(int $walletId): array
    {
        return $this->escrowRepository->findActiveByWallet($walletId);
    }

    public function getByUuid(string $uuid): ?EscrowHold
    {
        return $this->escrowRepository->findByUuid($uuid);
    }
}
