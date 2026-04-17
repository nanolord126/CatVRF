<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Domains\Wallet\Models\Wallet;
use App\Models\BalanceTransaction;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * WalletService — сервис управления кошельками (domain layer).
 *
 * Канон 2026: FraudControlService::check() → DB::transaction() → AuditService::log() → event().
 * Конструктор (порядок): DatabaseManager, LoggerInterface, Guard, FraudControlService,
 *                         AuditService, CacheRepository.
 *
 * @package App\Domains\Wallet\Services
 */
final readonly class WalletService
{
    /** Типы транзакций, допустимые для зачисления. */
    private const CREDIT_TYPES = [
        BalanceTransactionType::DEPOSIT,
        BalanceTransactionType::BONUS,
        BalanceTransactionType::REFUND,
        BalanceTransactionType::COMMISSION,
        BalanceTransactionType::RELEASE_HOLD,
    ];

    /** Типы транзакций, допустимые для списания. */
    private const DEBIT_TYPES = [
        BalanceTransactionType::WITHDRAWAL,
        BalanceTransactionType::HOLD,
        BalanceTransactionType::PAYOUT,
        BalanceTransactionType::COMMISSION,
    ];

    public function __construct(
        private DatabaseManager  $db,
        private LoggerInterface  $logger,
        private Guard            $guard,
        private FraudControlService $fraud,
        private AuditService     $audit,
        private CacheRepository  $cache,
    ) {}

    // ─── Public API ───────────────────────────────────────────────────

    public function credit(
        int                    $walletId,
        int                    $amount,
        BalanceTransactionType $type,
        string                 $correlationId,
        ?string                $sourceType = null,
        ?int                   $sourceId   = null,
        ?array                 $metadata   = null,
    ): Wallet {
        $this->guardAmount($amount);
        $this->guardCreditType($type);

        $userId = $this->getCurrentUserId() ?? 0;
        $this->fraud->check($userId, "wallet_credit_{$type->value}", $amount, null, null, $correlationId);

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

            $this->cache->forget("wallet:{$wallet->id}");

            return $wallet;
        });
    }

    public function debit(
        int                    $walletId,
        int                    $amount,
        BalanceTransactionType $type,
        string                 $correlationId,
        ?string                $sourceType = null,
        ?int                   $sourceId   = null,
        ?array                 $metadata   = null,
    ): Wallet {
        $this->guardAmount($amount);
        $this->guardDebitType($type);

        $userId = $this->getCurrentUserId() ?? 0;
        $this->fraud->check($userId, "wallet_debit_{$type->value}", $amount, null, null, $correlationId);

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
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $type->value,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    public function hold(
        int     $walletId,
        int     $amount,
        string  $correlationId,
        ?string $sourceType = null,
        ?int    $sourceId   = null,
        ?array  $metadata   = null,
    ): Wallet {
        $this->guardAmount($amount);

        $userId = $this->getCurrentUserId() ?? 0;
        $this->fraud->check($userId, 'wallet_hold', $amount, null, null, $correlationId);

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
                'wallet_id'      => $wallet->id,
                'amount'         => $amount,
                'correlation_id' => $correlationId,
            ]);

            $this->cache->forget("wallet:{$wallet->id}");

            return $wallet;
        });
    }

    // ─── Guard helpers ────────────────────────────────────────────────

    /**
     * Проверяет что сумма положительная.
     *
     * @throws \InvalidArgumentException
     */
    private function guardAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
    }

    /**
     * Проверяет что тип подходит для операции credit.
     *
     * @throws \InvalidArgumentException
     */
    private function guardCreditType(BalanceTransactionType $type): void
    {
        if (!\in_array($type, self::CREDIT_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid credit type: {$type->value}");
        }
    }

    /**
     * Проверяет что тип подходит для операции debit.
     *
     * @throws \InvalidArgumentException
     */
    private function guardDebitType(BalanceTransactionType $type): void
    {
        if (!\in_array($type, self::DEBIT_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid debit type: {$type->value}");
        }
    }

    /**
     * Возвращает ID текущего аутентифицированного пользователя или null.
     */
    private function getCurrentUserId(): ?int
    {
        $user = $this->guard->user();

        return $user ? (int) $user->getAuthIdentifier() : null;
    }
}
