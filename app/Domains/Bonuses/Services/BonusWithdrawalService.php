<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\Models\BonusWallet;
use App\Domains\Bonuses\Models\BonusTransaction;
use App\Domains\Bonuses\DTOs\WithdrawBonusDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Domains\Wallet\Services\WalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

/**
 * BonusWithdrawalService - Domain service for bonus withdrawal operations
 * 
 * Handles B2B Gold/Platinum bonus withdrawals to real money.
 * Integrates with Payment for actual payout processing.
 */
final readonly class BonusWithdrawalService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly WalletService $walletService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Process bonus withdrawal request
     */
    public function requestWithdrawal(
        WithdrawBonusDto $dto,
        BonusWallet $bonusWallet,
        ?string $correlationId = null,
    ): BonusTransaction {
        $correlationId ??= Str::uuid()->toString();

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'bonus_withdrawal_request',
            'amount' => $dto->amount,
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $bonusWallet, $correlationId) {
            // Create withdrawal transaction
            $transaction = BonusTransaction::create([
                'id' => (string) Str::uuid(),
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $dto->tenantId,
                'wallet_id' => $bonusWallet->id,
                'user_id' => $dto->userId,
                'type' => BonusTransaction::TYPE_WITHDRAW,
                'amount' => $dto->amount,
                'balance_after' => $bonusWallet->available_balance - $dto->amount,
                'status' => BonusTransaction::STATUS_WITHDRAWAL_PENDING,
                'withdrawal_method' => $dto->withdrawalMethod,
                'withdrawal_status' => 'pending',
                'metadata' => array_merge($dto->metadata ?? [], [
                    'bank_account_number' => $dto->bankAccountNumber,
                    'bank_name' => $dto->bankName,
                    'bic' => $dto->bic,
                    'inn' => $dto->inn,
                    'commission_amount' => $dto->getCommissionAmount(),
                    'net_amount' => $dto->getNetAmount(),
                ]),
                'correlation_id' => $correlationId,
                'tags' => ['bonus', 'withdrawal', 'pending'],
            ]);

            // Hold the amount in bonus wallet
            $bonusWallet->debitAvailable($dto->amount);

            // Log audit
            $this->audit->record(
                'bonus_withdrawal_requested',
                BonusTransaction::class,
                null,
                [],
                [
                    'user_id' => $dto->userId,
                    'amount' => $dto->amount,
                    'net_amount' => $dto->getNetAmount(),
                    'withdrawal_method' => $dto->withdrawalMethod,
                    'transaction_id' => $transaction->id,
                ],
                $correlationId,
            );

            $this->logger->info('Bonus withdrawal requested', [
                'transaction_id' => $transaction->id,
                'user_id' => $dto->userId,
                'amount' => $dto->amount,
                'net_amount' => $dto->getNetAmount(),
                'correlation_id' => $correlationId,
            ]);

            return $transaction;
        });
    }

    /**
     * Approve and process withdrawal
     */
    public function approveWithdrawal(
        BonusTransaction $transaction,
        BonusWallet $bonusWallet,
        ?string $correlationId = null,
    ): void {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($transaction, $bonusWallet, $correlationId) {
            // Mark transaction as completed
            $transaction->markAsWithdrawn('completed');

            // Credit to main wallet (net amount after commission)
            $metadata = $transaction->metadata;
            $netAmount = $metadata['net_amount'] ?? $transaction->amount;

            $this->walletService->credit(
                walletId: $bonusWallet->main_wallet_id,
                amount: $netAmount,
                reason: 'Bonus withdrawal',
                correlationId: $correlationId,
                metadata: [
                    'bonus_transaction_id' => $transaction->id,
                    'withdrawal_method' => $transaction->withdrawal_method,
                ],
            );

            // Update bonus wallet stats
            $bonusWallet->withdraw($transaction->amount);

            // Log audit
            $this->audit->record(
                'bonus_withdrawal_approved',
                BonusTransaction::class,
                null,
                [],
                [
                    'user_id' => $transaction->user_id,
                    'amount' => $transaction->amount,
                    'net_amount' => $netAmount,
                    'transaction_id' => $transaction->id,
                ],
                $correlationId,
            );

            $this->logger->info('Bonus withdrawal approved', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Reject withdrawal
     */
    public function rejectWithdrawal(
        BonusTransaction $transaction,
        BonusWallet $bonusWallet,
        string $reason,
        ?string $correlationId = null,
    ): void {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($transaction, $bonusWallet, $reason, $correlationId) {
            // Mark transaction as rejected
            $transaction->status = BonusTransaction::STATUS_WITHDRAWAL_REJECTED;
            $transaction->withdrawal_status = 'rejected';
            $transaction->metadata = array_merge($transaction->metadata ?? [], [
                'rejection_reason' => $reason,
                'rejected_at' => CarbonImmutable::now()->toIso8601String(),
            ]);
            $transaction->save();

            // Return amount to bonus wallet
            $bonusWallet->creditAvailable($transaction->amount);

            // Log audit
            $this->audit->record(
                'bonus_withdrawal_rejected',
                BonusTransaction::class,
                null,
                [],
                [
                    'user_id' => $transaction->user_id,
                    'amount' => $transaction->amount,
                    'reason' => $reason,
                    'transaction_id' => $transaction->id,
                ],
                $correlationId,
            );

            $this->logger->warning('Bonus withdrawal rejected', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Get pending withdrawals
     */
    public function getPendingWithdrawals(?string $tenantId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = BonusTransaction::where('type', BonusTransaction::TYPE_WITHDRAW)
            ->where('status', BonusTransaction::STATUS_WITHDRAWAL_PENDING);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Process pending withdrawals in batch
     */
    public function processPendingWithdrawals(?string $tenantId = null, ?string $correlationId = null): int
    {
        $correlationId ??= Str::uuid()->toString();
        $processedCount = 0;

        $pendingWithdrawals = $this->getPendingWithdrawals($tenantId);

        foreach ($pendingWithdrawals as $withdrawal) {
            try {
                $wallet = BonusWallet::findOrFail($withdrawal->wallet_id);
                
                // Auto-approve for demo purposes
                // In production, this would require manual approval or integration with payment system
                $this->approveWithdrawal($withdrawal, $wallet, $correlationId);
                
                $processedCount++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to process withdrawal', [
                    'transaction_id' => $withdrawal->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        $this->logger->info('Processed pending withdrawals', [
            'processed_count' => $processedCount,
            'correlation_id' => $correlationId,
        ]);

        return $processedCount;
    }
}
