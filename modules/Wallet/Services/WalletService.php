<?php declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DomainException;
use Throwable;

/**
 * Сервис управления кошельком.
 * Единственная точка для операций с балансом.
 * Production 2026.
 */
final class WalletService
{
    /**
     * Получить текущий баланс пользователя.
     */
    public function getBalance(int $userId, int $tenantId): int
    {
        return WalletTransaction::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Пополнить кошелек (deposit).
     * @throws DomainException
     */
    public function deposit(
        int $userId,
        int $tenantId,
        int $amountCents,
        string $currency = 'RUB',
        ?array $metadata = null,
        ?string $description = null,
    ): WalletTransaction {
        $correlationId = Str::uuid();

        try {
            // Fraud check - не делаем здесь, это должно быть в контроллере
            // WalletService —純粹 бизнес-логика, fraud check на уровне API контроллера

            Log::channel('audit')->info('wallet.service.deposit.start', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amountCents,
            ]);

            // Транзакция БД
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
                    'correlation_id' => (string) $correlationId,
                    'tags' => ['deposit', 'completed'],
                    'metadata' => $metadata ?? [],
                    'description' => $description,
                ]);
            });

            Log::channel('audit')->info('wallet.service.deposit.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $amountCents,
            ]);

            return $transaction;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('wallet.service.deposit.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Вывести с кошелька (withdrawal).
     * @throws DomainException
     */
    public function withdraw(
        int $userId,
        int $tenantId,
        int $amountCents,
        string $currency = 'RUB',
        ?array $metadata = null,
        ?string $description = null,
    ): WalletTransaction {
        $correlationId = Str::uuid();

        try {
            // Fraud check - не делаем здесь, это должно быть в контроллере
            // WalletService — бизнес-логика, fraud check на уровне API контроллера

            Log::channel('audit')->info('wallet.service.withdraw.start', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amountCents,
            ]);

            // Проверка баланса и транзакция
            $transaction = DB::transaction(function () use (
                $userId,
                $tenantId,
                $amountCents,
                $currency,
                $metadata,
                $description,
                $correlationId,
            ) {
                $currentBalance = WalletTransaction::where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->where('status', 'completed')
                    ->sum('amount');

                if ($currentBalance < $amountCents) {
                    throw new DomainException(
                        sprintf(
                            'Insufficient balance: required %d, available %d',
                            $amountCents,
                            $currentBalance
                        )
                    );
                }

                return WalletTransaction::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'type' => 'withdrawal',
                    'amount' => -$amountCents,
                    'status' => 'completed',
                    'currency' => $currency,
                    'correlation_id' => (string) $correlationId,
                    'tags' => ['withdrawal', 'completed'],
                    'metadata' => $metadata ?? [],
                    'description' => $description,
                ]);
            });

            Log::channel('audit')->info('wallet.service.withdraw.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $amountCents,
            ]);

            return $transaction;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('wallet.service.withdraw.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить историю транзакций.
     */
    public function getHistory(int $userId, int $tenantId, int $perPage = 20): \Illuminate\Pagination\Paginator
    {
        return WalletTransaction::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
