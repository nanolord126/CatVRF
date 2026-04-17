<?php declare(strict_types=1);

namespace Modules\Wallet\Services;

use App\Modules\Wallet\Models\WalletTransaction;
use App\Services\FraudControlService;
use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use DomainException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Сервис управления кошельком (Wallet Service)
 *
 * КАНОН 2026 - Production Ready
 * Единственная точка для операций с балансом.
 * Все операции требуют:
 * 1. FraudControlService::check() перед мутацией
 * 2. DB::transaction() для атомарности
 * 3. correlation_id для трейсирования
 * 4. Log::channel('audit') для всех операций
 * 5. Exception handling с полным backtrace
 */
final class WalletService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
        private readonly PaymentFraudMLService $fraudMl,
    ) {}

    /**
     * Получить текущий баланс пользователя
     *
     * @param int $userId
     * @param int $tenantId
     * @return int (копейки)
     */
    public function getBalance(int $userId, int $tenantId): int
    {
        return WalletTransaction::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Пополнить кошелек (deposit)
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $amountCents (копейки)
     * @param string $currency
     * @param ?array $metadata
     * @param ?string $description
     * @param ?string $correlationId
     * @return WalletTransaction
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function deposit(
        int $userId,
        int $tenantId,
        int $amountCents,
        string $currency = 'RUB',
        ?array $metadata = null,
        ?string $description = null,
        ?string $correlationId = null,
    ): WalletTransaction {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. RULE-BASED FRAUD CHECK (legacy, still required)
            $this->fraud->check([
                'operation_type' => 'wallet_deposit',
                'amount' => $amountCents,
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            // 2. ML-BASED FRAUD CHECK (payment-specific for wallet-drain protection)
            $fraudMlResult = $this->performWalletFraudCheck(
                $userId,
                $tenantId,
                $amountCents,
                'wallet_deposit',
                $correlationId,
            );

            if ($fraudMlResult['decision'] === 'block') {
                Log::channel('audit')->warning('Wallet: Deposit blocked by ML fraud detection', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'amount' => $amountCents,
                    'fraud_score' => $fraudMlResult['score'],
                    'explanation' => $fraudMlResult['explanation'] ?? null,
                ]);
                throw new DomainException('Deposit blocked by fraud detection system');
            }

            Log::channel('audit')->info('Wallet: Deposit initiated', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'amount' => $amountCents,
                'currency' => $currency,
            ]);

            // 2. DATABASE TRANSACTION
            $transaction = DB::transaction(function () use (
                $userId,
                $tenantId,
                $amountCents,
                $currency,
                $metadata,
                $description,
                $correlationId,
            ) {
                return WalletTransaction::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'type' => 'deposit',
                    'amount' => $amountCents,
                    'status' => 'completed',
                    'currency' => $currency,
                    'correlation_id' => $correlationId,
                    'tags' => array_merge(['deposit', 'completed'], $fraudMlResult['cached'] ? ['fraud_cached'] : []),
                    'metadata' => array_merge($metadata ?? [], [
                        'fraud_score' => $fraudMlResult['score'],
                        'fraud_decision' => $fraudMlResult['decision'],
                        'fraud_cached' => $fraudMlResult['cached'],
                    ]),
                    'description' => $description,
                ]);
            });

            // 3. SUCCESS LOG
            Log::channel('audit')->info('Wallet: Deposit succeeded', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $amountCents,
                'new_balance' => $this->getBalance($userId, $tenantId),
            ]);

            return $transaction;
        } catch (\Exception $e) {
            // 4. ERROR LOG with backtrace
            if ($e instanceof DomainException) {
                Log::channel('audit')->warning('Wallet: Deposit failed - domain error', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
            } else {
                Log::channel('audit')->error('Wallet: Deposit error', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Вывести с кошелька (withdrawal)
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $amountCents (копейки)
     * @param string $currency
     * @param ?array $metadata
     * @param ?string $description
     * @param ?string $correlationId
     * @return WalletTransaction
     *
     * @throws DomainException (недостаточно средств)
     * @throws Throwable
     */
    public function withdraw(
        int $userId,
        int $tenantId,
        int $amountCents,
        string $currency = 'RUB',
        ?array $metadata = null,
        ?string $description = null,
        ?string $correlationId = null,
    ): WalletTransaction {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. RULE-BASED FRAUD CHECK (legacy, still required)
            $this->fraud->check([
                'operation_type' => 'wallet_withdrawal',
                'amount' => $amountCents,
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            // 2. ML-BASED FRAUD CHECK (payment-specific for wallet-drain protection)
            $fraudMlResult = $this->performWalletFraudCheck(
                $userId,
                $tenantId,
                $amountCents,
                'wallet_withdrawal',
                $correlationId,
            );

            if ($fraudMlResult['decision'] === 'block') {
                Log::channel('audit')->warning('Wallet: Withdrawal blocked by ML fraud detection', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'amount' => $amountCents,
                    'fraud_score' => $fraudMlResult['score'],
                    'explanation' => $fraudMlResult['explanation'] ?? null,
                ]);
                throw new DomainException('Withdrawal blocked by fraud detection system');
            }

            Log::channel('audit')->info('Wallet: Withdrawal initiated', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'amount' => $amountCents,
                'currency' => $currency,
            ]);

            // 2. DATABASE TRANSACTION with balance check
            $transaction = DB::transaction(function () use (
                $userId,
                $tenantId,
                $amountCents,
                $currency,
                $metadata,
                $description,
                $correlationId,
            ) {
                // Check balance INSIDE transaction (atomic)
                $currentBalance = WalletTransaction::where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->where('status', 'completed')
                    ->sum('amount');

                if ($currentBalance < $amountCents) {
                    throw new DomainException(
                        sprintf(
                            'Insufficient balance: required %d cents, available %d cents',
                            $amountCents,
                            $currentBalance
                        )
                    );
                }

                return WalletTransaction::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'type' => 'withdrawal',
                    'amount' => -$amountCents,  // negative amount
                    'status' => 'completed',
                    'currency' => $currency,
                    'correlation_id' => $correlationId,
                    'tags' => array_merge(['withdrawal', 'completed'], $fraudMlResult['cached'] ? ['fraud_cached'] : []),
                    'metadata' => array_merge($metadata ?? [], [
                        'fraud_score' => $fraudMlResult['score'],
                        'fraud_decision' => $fraudMlResult['decision'],
                        'fraud_cached' => $fraudMlResult['cached'],
                    ]),
                    'description' => $description,
                ]);
            });

            // 3. SUCCESS LOG
            Log::channel('audit')->info('Wallet: Withdrawal succeeded', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $amountCents,
                'new_balance' => $this->getBalance($userId, $tenantId),
            ]);

            return $transaction;
        } catch (\Exception $e) {
            // 4. ERROR LOG with context
            if ($e instanceof DomainException) {
                Log::channel('audit')->warning('Wallet: Withdrawal failed - insufficient balance', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'requested_amount' => $amountCents,
                    'current_balance' => $this->getBalance($userId, $tenantId),
                    'error' => $e->getMessage(),
                ]);
            } else {
                Log::channel('audit')->error('Wallet: Withdrawal error', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Получить историю транзакций (пагинированно)
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $perPage
     * @return \Illuminate\Pagination\Paginator
     */
    public function getHistory(int $userId, int $tenantId, int $perPage = 20): \Illuminate\Pagination\Paginator
    {
        return WalletTransaction::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Перевести средства между пользователями (внутри платформы)
     *
     * @param int $fromUserId
     * @param int $toUserId
     * @param int $tenantId
     * @param int $amountCents
     * @param ?string $correlationId
     * @return array ['from' => WalletTransaction, 'to' => WalletTransaction]
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function transfer(
        int $fromUserId,
        int $toUserId,
        int $tenantId,
        int $amountCents,
        ?string $correlationId = null,
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK
            $this->fraud->check([
                'operation_type' => 'wallet_transfer',
                'amount' => $amountCents,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Wallet: Transfer initiated', [
                'correlation_id' => $correlationId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amountCents,
            ]);

            // 2. TRANSACTION
            $result = DB::transaction(function () use (
                $fromUserId,
                $toUserId,
                $tenantId,
                $amountCents,
                $correlationId,
            ) {
                // Check sender balance
                $senderBalance = $this->getBalance($fromUserId, $tenantId);
                if ($senderBalance < $amountCents) {
                    throw new DomainException('Insufficient balance for transfer');
                }

                // Debit sender
                $debit = WalletTransaction::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $fromUserId,
                    'type' => 'transfer_out',
                    'amount' => -$amountCents,
                    'status' => 'completed',
                    'correlation_id' => $correlationId,
                    'tags' => ['transfer', 'completed'],
                ]);

                // Credit recipient
                $credit = WalletTransaction::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $toUserId,
                    'type' => 'transfer_in',
                    'amount' => $amountCents,
                    'status' => 'completed',
                    'correlation_id' => $correlationId,
                    'tags' => ['transfer', 'completed'],
                ]);

                return [
                    'from' => $debit,
                    'to' => $credit,
                ];
            });

            // 3. SUCCESS LOG
            Log::channel('audit')->info('Wallet: Transfer succeeded', [
                'correlation_id' => $correlationId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amountCents,
            ]);

            return $result;
        } catch (\Exception $e) {
            // 4. ERROR LOG
            Log::channel('audit')->error('Wallet: Transfer failed', [
                'correlation_id' => $correlationId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amountCents,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Заморозить средства (hold) для зарезервированной операции
     *
     * Используется при заказе, но до фактической оплаты
     * (например, бронирование услуги или товара)
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $amountCents
     * @param string $holdReason (appointment, order, booking)
     * @param ?string $correlationId
     * @return WalletTransaction
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function hold(
        int $userId,
        int $tenantId,
        int $amountCents,
        string $holdReason = 'appointment',
        ?string $correlationId = null,
    ): WalletTransaction {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK
            $this->fraud->check([
                'operation_type' => 'wallet_hold',
                'amount' => $amountCents,
                'user_id' => $userId,
                'hold_reason' => $holdReason,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Wallet: Hold initiated', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amountCents,
                'reason' => $holdReason,
            ]);

            // 2. TRANSACTION
            $transaction = DB::transaction(function () use (
                $userId,
                $tenantId,
                $amountCents,
                $holdReason,
                $correlationId,
            ) {
                // Check balance
                $currentBalance = $this->getBalance($userId, $tenantId);
                if ($currentBalance < $amountCents) {
                    throw new DomainException('Insufficient balance for hold');
                }

                return WalletTransaction::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'type' => 'hold',
                    'amount' => -$amountCents,
                    'status' => 'pending',  // pending until release
                    'correlation_id' => $correlationId,
                    'tags' => ['hold', 'pending', $holdReason],
                    'description' => "Hold for {$holdReason}",
                ]);
            });

            // 3. SUCCESS LOG
            Log::channel('audit')->info('Wallet: Hold succeeded', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $amountCents,
                'reason' => $holdReason,
            ]);

            return $transaction;
        } catch (\Exception $e) {
            // 4. ERROR LOG
            Log::channel('audit')->error('Wallet: Hold failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Освободить заморозленные средства (release hold)
     *
     * @param int $transactionId (ID записи hold из WalletTransaction)
     * @param ?string $correlationId
     * @return void
     *
     * @throws Throwable
     */
    public function releaseHold(int $transactionId, ?string $correlationId = null): void
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            $holdTransaction = WalletTransaction::findOrFail($transactionId);

            if ($holdTransaction->type !== 'hold' || $holdTransaction->status !== 'pending') {
                throw new DomainException('Transaction is not a pending hold');
            }

            Log::channel('audit')->info('Wallet: Release hold initiated', [
                'correlation_id' => $correlationId,
                'hold_transaction_id' => $transactionId,
                'amount' => abs($holdTransaction->amount),
            ]);

            // UPDATE the hold transaction status
            $holdTransaction->update([
                'status' => 'released',
                'correlation_id' => $correlationId,
            ]);

            // CREATE reversal transaction
            DB::transaction(function () use ($holdTransaction, $correlationId) {
                WalletTransaction::create([
                    'tenant_id' => $holdTransaction->tenant_id,
                    'user_id' => $holdTransaction->user_id,
                    'type' => 'hold_release',
                    'amount' => abs($holdTransaction->amount),  // positive (reverse the hold)
                    'status' => 'completed',
                    'correlation_id' => $correlationId,
                    'tags' => ['hold_release', 'completed'],
                    'description' => "Release hold from transaction {$holdTransaction->id}",
                ]);
            });

            Log::channel('audit')->info('Wallet: Release hold succeeded', [
                'correlation_id' => $correlationId,
                'hold_transaction_id' => $transactionId,
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Wallet: Release hold failed', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Perform ML-based fraud check for wallet operations
     * 
     * Critical for wallet-drain attack detection
     */
    private function performWalletFraudCheck(
        int $userId,
        int $tenantId,
        int $amountCents,
        string $operationType,
        string $correlationId,
    ): array {
        $startTime = microtime(true);

        try {
            $currentBalance = $this->getBalance($userId, $tenantId);

            $dto = new PaymentFraudMLDto(
                tenant_id: $tenantId,
                user_id: $userId,
                operation_type: $operationType,
                amount_kopecks: $amountCents,
                ip_address: request()->ip() ?? '127.0.0.1',
                device_fingerprint: request()->header('User-Agent') ?? 'unknown',
                correlation_id: $correlationId,
                idempotency_key: $correlationId,
                vertical_code: 'wallet',
                wallet_balance_kopecks: $currentBalance,
            );

            $result = $this->fraudMl->scorePayment($dto);
            
            $latencyMs = (microtime(true) - $startTime) * 1000;
            
            if ($latencyMs > 30) {
                Log::warning('wallet.fraud.check.high_latency', [
                    'correlation_id' => $correlationId,
                    'latency_ms' => $latencyMs,
                ]);
            }

            return $result;
            
        } catch (Throwable $e) {
            Log::error('wallet.fraud.check.failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            
            // Fail-open for reliability
            return [
                'score' => 0.0,
                'decision' => 'allow',
                'explanation' => ['error' => 'fraud_check_failed'],
                'cached' => false,
            ];
        }
    }
}
