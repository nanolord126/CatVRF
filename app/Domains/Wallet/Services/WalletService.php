<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\BalanceTransaction;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\Fraud\FraudControlService;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

final readonly class WalletService
{
    public function __construct(
        private DatabaseManager $db,
        private FraudControlService $fraud,
        private AuditService $audit,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    public function credit(
        int $walletId,
        int $amount,
        BalanceTransactionType $type,
        string $correlationId,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null
    ): Wallet {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be greater than zero.');
        }

        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check($userId, "wallet_credit_{$type->value}", $amount, null, null, $correlationId);

        return $this->db->transaction(function () use ($walletId, $amount, $type, $correlationId, $sourceType, $sourceId, $metadata, $userId): Wallet {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($walletId);
            $oldBalance = $wallet->current_balance;

            $wallet->current_balance += $amount;

            if ($type === BalanceTransactionType::RELEASE_HOLD) {
                if ($wallet->hold_amount < $amount) {
                    throw new \RuntimeException('Not enough hold amount to release.');
                }
                $wallet->hold_amount -= $amount;
            }

            $wallet->save();

            BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type->value,
                'amount' => $amount,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'metadata' => $metadata,
            ]);

            $this->audit->log('wallet_credited', Wallet::class, $wallet->id, [
                'current_balance' => $oldBalance
            ], [
                'current_balance' => $wallet->current_balance
            ], $correlationId);

            $this->logger->info('Wallet credited', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $type->value,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    public function debit(
        int $walletId,
        int $amount,
        BalanceTransactionType $type,
        string $correlationId,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null
    ): Wallet {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be greater than zero.');
        }

        $userId = (int) ($this->guard->id() ?? 0);
        $this->fraud->check($userId, "wallet_debit_{$type->value}", $amount, null, null, $correlationId);

        return $this->db->transaction(function () use ($walletId, $amount, $type, $correlationId, $sourceType, $sourceId, $metadata, $userId): Wallet {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($walletId);
            $oldBalance = $wallet->current_balance;

            if ($wallet->current_balance < $amount) {
                throw new \RuntimeException('Insufficient balance.');
            }

            $wallet->current_balance -= $amount;
            $wallet->save();

            BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type->value,
                'amount' => $amount,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'metadata' => $metadata,
            ]);

            $this->audit->log('wallet_debited', Wallet::class, $wallet->id, [
                'current_balance' => $oldBalance
            ], [
                'current_balance' => $wallet->current_balance
            ], $correlationId);

            $this->logger->info('Wallet debited', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $type->value,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    public function hold(
        int $walletId,
        int $amount,
        string $correlationId,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null
    ): Wallet {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Hold amount must be greater than zero.');
        }

        $userId = (int) ($this->guard->id() ?? 0);
        $this->fraud->check($userId, "wallet_hold", $amount, null, null, $correlationId);

        return $this->db->transaction(function () use ($walletId, $amount, $correlationId, $sourceType, $sourceId, $metadata, $userId): Wallet {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($walletId);
            $oldBalance = $wallet->current_balance;
            $oldHold = $wallet->hold_amount;

            if ($wallet->current_balance < $amount) {
                throw new \RuntimeException('Insufficient balance for hold.');
            }

            $wallet->current_balance -= $amount;
            $wallet->hold_amount += $amount;
            $wallet->save();

            BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => BalanceTransactionType::HOLD->value,
                'amount' => $amount,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'metadata' => $metadata,
            ]);

            $this->audit->log('wallet_hold', Wallet::class, $wallet->id, [
                'current_balance' => $oldBalance,
                'hold_amount' => $oldHold,
            ], [
                'current_balance' => $wallet->current_balance,
                'hold_amount' => $wallet->hold_amount,
            ], $correlationId);

            $this->logger->info('Wallet amount held', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }
}
