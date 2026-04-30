<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

/**
 * BonusBalanceDto - Data transfer object for bonus balance information
 * 
 * Provides complete balance information including pending and available amounts.
 */
final readonly class BonusBalanceDto
{
    public function __construct(
        public string $userId,
        public string $walletId,
        public int $availableBalance,
        public int $pendingBalance,
        public int $totalEarned,
        public int $totalSpent,
        public int $totalWithdrawn,
        public string $userType,
        public ?string $tier,
        public bool $canWithdraw,
        public int $maxBalance,
        public int $maxWithdrawalPercentage,
        public int $transactionCount,
        public ?string $lastTransactionAt,
    ) {}

    public static function fromModel(\App\Domains\Bonuses\Models\BonusWallet $wallet): self
    {
        return new self(
            userId: $wallet->user_id,
            walletId: $wallet->id,
            availableBalance: $wallet->available_balance,
            pendingBalance: $wallet->pending_balance,
            totalEarned: $wallet->total_earned,
            totalSpent: $wallet->total_spent,
            totalWithdrawn: $wallet->total_withdrawn,
            userType: $wallet->user_type,
            tier: $wallet->tier,
            canWithdraw: $wallet->can_withdraw,
            maxBalance: $wallet->max_balance,
            maxWithdrawalPercentage: $wallet->max_withdrawal_percentage,
            transactionCount: $wallet->transaction_count,
            lastTransactionAt: $wallet->last_transaction_at?->toIso8601String(),
        );
    }

    public function getTotalBalance(): int
    {
        return $this->availableBalance + $this->pendingBalance;
    }

    public function getAvailableWithdrawalAmount(): int
    {
        if (!$this->canWithdraw) {
            return 0;
        }

        return (int) ($this->availableBalance * $this->maxWithdrawalPercentage / 100);
    }

    public function getBalancePercentage(): float
    {
        if ($this->maxBalance === 0) {
            return 0.0;
        }

        return ($this->getTotalBalance() / $this->maxBalance) * 100;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'wallet_id' => $this->walletId,
            'available_balance' => $this->availableBalance,
            'pending_balance' => $this->pendingBalance,
            'total_balance' => $this->getTotalBalance(),
            'total_earned' => $this->totalEarned,
            'total_spent' => $this->totalSpent,
            'total_withdrawn' => $this->totalWithdrawn,
            'user_type' => $this->userType,
            'tier' => $this->tier,
            'can_withdraw' => $this->canWithdraw,
            'available_withdrawal_amount' => $this->getAvailableWithdrawalAmount(),
            'max_balance' => $this->maxBalance,
            'max_withdrawal_percentage' => $this->maxWithdrawalPercentage,
            'balance_percentage' => $this->getBalancePercentage(),
            'transaction_count' => $this->transactionCount,
            'last_transaction_at' => $this->lastTransactionAt,
        ];
    }
}
