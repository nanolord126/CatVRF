<?php declare(strict_types=1);

namespace App\Domains\Wallet\Domain\Services;

use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Domains\Wallet\Models\Wallet;
use App\Models\BalanceTransaction;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;

/**
 * WalletService — доменный сервис управления кошельками.
 *
 * Канон 2026:
 * - FraudControlService::check() перед каждой мутацией
 * - DB::transaction() + lockForUpdate()
 * - AuditService::log() после успешной операции
 * - correlation_id обязателен
 * - hold / releaseHold / credit / debit
 *
 * @package App\Domains\Wallet\Domain\Services
 */
final readonly class WalletService
{
    public function __construct(
        private DatabaseManager    $db,
        private FraudControlService $fraud,
        private AuditService       $audit,
        private LoggerInterface    $logger,
        private Guard              $guard,
    ) {}

    /**
     * Зачислить средства на кошелёк.
     */
    public function credit(
        int                    $walletId,
        int                    $amount,
        BalanceTransactionType $type,
        string                 $correlationId,
        ?string                $sourceType = null,
        ?int                   $sourceId   = null,
        ?array                 $metadata   = null,
    ): Wallet {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be greater than zero.');
        }

        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: "wallet_credit_{$type->value}",
            amount: $amount,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use (
            $walletId, $amount, $type, $correlationId, $sourceType, $sourceId, $metadata
        ): Wallet {
            /** @var Wallet $wallet */
            $wallet     = Wallet::query()->lockForUpdate()->findOrFail($walletId);
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
                'wallet_id'      => $wallet->id,
                'type'           => $type->value,
                'amount'         => $amount,
                'source_type'    => $sourceType,
                'source_id'      => $sourceId,
                'correlation_id' => $correlationId,
                'metadata'       => $metadata,
            ]);

            $this->audit->log('wallet_credited', Wallet::class, $wallet->id, [
                'current_balance' => $oldBalance,
            ], [
                'current_balance' => $wallet->current_balance,
            ], $correlationId);

            $this->logger->info('Wallet credited', [
                'wallet_id'      => $wallet->id,
                'amount'         => $amount,
                'type'           => $type->value,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    /**
     * Списать средства с кошелька.
     */
    public function debit(
        int                    $walletId,
        int                    $amount,
        BalanceTransactionType $type,
        string                 $correlationId,
        ?string                $sourceType = null,
        ?int                   $sourceId   = null,
        ?array                 $metadata   = null,
    ): Wallet {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be greater than zero.');
        }

        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: "wallet_debit_{$type->value}",
            amount: $amount,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use (
            $walletId, $amount, $type, $correlationId, $sourceType, $sourceId, $metadata
        ): Wallet {
            /** @var Wallet $wallet */
            $wallet     = Wallet::query()->lockForUpdate()->findOrFail($walletId);
            $oldBalance = $wallet->current_balance;

            if ($wallet->current_balance < $amount) {
                throw new \RuntimeException('Insufficient balance.');
            }

            $wallet->current_balance -= $amount;
            $wallet->save();

            BalanceTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => $type->value,
                'amount'         => $amount,
                'source_type'    => $sourceType,
                'source_id'      => $sourceId,
                'correlation_id' => $correlationId,
                'metadata'       => $metadata,
            ]);

            $this->audit->log('wallet_debited', Wallet::class, $wallet->id, [
                'current_balance' => $oldBalance,
            ], [
                'current_balance' => $wallet->current_balance,
            ], $correlationId);

            $this->logger->info('Wallet debited', [
                'wallet_id'      => $wallet->id,
                'amount'         => $amount,
                'type'           => $type->value,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    /**
     * Зарезервировать (удержать) средства на кошельке.
     *
     * Средства списываются с current_balance и зачисляются в hold_amount.
     */
    public function hold(
        int     $walletId,
        int     $amount,
        string  $correlationId,
        ?string $sourceType = null,
        ?int    $sourceId   = null,
        ?array  $metadata   = null,
    ): Wallet {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Hold amount must be greater than zero.');
        }

        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: 'wallet_hold',
            amount: $amount,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use (
            $walletId, $amount, $correlationId, $sourceType, $sourceId, $metadata
        ): Wallet {
            /** @var Wallet $wallet */
            $wallet     = Wallet::query()->lockForUpdate()->findOrFail($walletId);
            $oldBalance = $wallet->current_balance;
            $oldHold    = $wallet->hold_amount;

            if ($wallet->current_balance < $amount) {
                throw new \RuntimeException('Insufficient balance for hold.');
            }

            $wallet->current_balance -= $amount;
            $wallet->hold_amount     += $amount;
            $wallet->save();

            BalanceTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => BalanceTransactionType::HOLD->value,
                'amount'         => $amount,
                'source_type'    => $sourceType,
                'source_id'      => $sourceId,
                'correlation_id' => $correlationId,
                'metadata'       => $metadata,
            ]);

            $this->audit->log('wallet_hold', Wallet::class, $wallet->id, [
                'current_balance' => $oldBalance,
                'hold_amount'     => $oldHold,
            ], [
                'current_balance' => $wallet->current_balance,
                'hold_amount'     => $wallet->hold_amount,
            ], $correlationId);

            $this->logger->info('Wallet amount held', [
                'wallet_id'      => $wallet->id,
                'amount'         => $amount,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    /**
     * Освободить зарезервированные средства.
     *
     * Средства возвращаются из hold_amount → current_balance.
     */
    public function releaseHold(
        int     $walletId,
        int     $amount,
        string  $correlationId,
        ?string $sourceType = null,
        ?int    $sourceId   = null,
    ): Wallet {
        return $this->credit(
            walletId:       $walletId,
            amount:         $amount,
            type:           BalanceTransactionType::RELEASE_HOLD,
            correlationId:  $correlationId,
            sourceType:     $sourceType,
            sourceId:       $sourceId,
        );
    }
}
