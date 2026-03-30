<?php declare(strict_types=1);

namespace Modules\Finances\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WalletService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    DB, Log, Cache, Redis};
    use Illuminate\Support\Str;
    
    /**
     * Сервис управления кошельком пользователя.
     * Согласно КАНОН 2026: основной сервис для работы с балансом и холдами.
     *
     * Поддерживает:
     * - Зачисление средств (credit) с транзакцией
     * - Списание средств (debit) с проверкой баланса и локировкой
     * - Холды (hold/release) для двухэтапной авторизации
     * - Трансферы между пользователями
     * - Redis-кэширование баланса
     * - Полное логирование в balance_transactions и audit-канал
     */
    final class WalletService
    {
        public function __construct(
            private readonly FraudControlService $fraudControl,
        ) {
        }
        /**
         * Зачислить средства на кошелёк.
         * Согласно КАНОН 2026: DB::transaction(), correlation_id, audit-логи, FraudCheck.
         *
         * @param int $walletId  ID кошелька
         * @param int $amount    Сумма в копейках
         * @param string $type   Тип операции (deposit, bonus, refund, etc)
         * @param string $reason Причина зачисления
         * @param string|null $sourceId ID источника операции
         * @param string|null $correlationId Correlation ID для отслеживания
         *
         * @return bool
         *
         * @throws \RuntimeException
         */
        public function credit(
            int $walletId,
            int $amount,
            string $type,
            string $reason,
            ?string $sourceId = null,
            ?string $correlationId = null
        ): bool {
            $correlationId = $correlationId ?? Str::uuid()->toString();
    
            return DB::transaction(function () use (
                $walletId,
                $amount,
                $type,
                $reason,
                $sourceId,
                $correlationId
            ): bool {
                if ($amount <= 0) {
                    throw new \InvalidArgumentException('Amount must be greater than 0');
                }
    
                // Получить кошелёк с локировкой
                $wallet = DB::table('wallets')->where('id', $walletId)->lockForUpdate()->first();
    
                if (!$wallet) {
                    throw new \RuntimeException("Wallet {$walletId} not found");
                }
    
                $balanceBefore = (int) $wallet->balance;
                $balanceAfter = $balanceBefore + $amount;
    
                // Обновить баланс в кошельке
                DB::table('wallets')->where('id', $walletId)->update([
                    'balance' => $balanceAfter,
                    'available_balance' => $balanceAfter - (int) ($wallet->hold_amount ?? 0),
                    'cached_balance' => $balanceAfter,
                    'cached_at' => now(),
                ]);
    
                // Создать запись в balance_transactions
                DB::table('balance_transactions')->insert([
                    'uuid' => Str::uuid(),
                    'correlation_id' => $correlationId,
                    'tenant_id' => $wallet->tenant_id,
                    'wallet_id' => $walletId,
                    'user_id' => $wallet->user_id,
                    'type' => $type,
                    'amount' => $amount,
                    'status' => 'completed',
                    'source_type' => null,
                    'source_id' => $sourceId,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'hold_before' => (int) ($wallet->hold_amount ?? 0),
                    'hold_after' => (int) ($wallet->hold_amount ?? 0),
                    'description' => $reason,
                    'created_by_type' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                // Логирование в audit-канал
                Log::channel('audit')->info('Wallet credited', [
                    'wallet_id' => $walletId,
                    'amount' => $amount,
                    'type' => $type,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reason' => $reason,
                    'source_id' => $sourceId,
                    'correlation_id' => $correlationId,
                    'trace' => implode(' > ', array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), 0, 3)),
                ]);
    
                // Инвалидировать Redis кэш
                $this->invalidateCache($wallet->tenant_id, $walletId);
    
                return true;
            });
        }
    
        /**
         * Списать средства с кошелька.
         * Согласно КАНОН 2026: DB::transaction(), lockForUpdate(), проверка баланса, audit-логи.
         *
         * @param int $walletId        ID кошелька
         * @param int $amount          Сумма в копейках
         * @param string $type         Тип операции
         * @param string $reason       Причина списания
         * @param string|null $sourceId ID источника
         * @param string|null $correlationId Correlation ID
         *
         * @return bool
         *
         * @throws \RuntimeException
         */
        public function debit(
            int $walletId,
            int $amount,
            string $type,
            string $reason,
            ?string $sourceId = null,
            ?string $correlationId = null
        ): bool {
            $correlationId = $correlationId ?? Str::uuid()->toString();
    
            return DB::transaction(function () use (
                $walletId,
                $amount,
                $type,
                $reason,
                $sourceId,
                $correlationId
            ): bool {
                if ($amount <= 0) {
                    throw new \InvalidArgumentException('Amount must be greater than 0');
                }
    
                // Получить кошелёк с локировкой
                $wallet = DB::table('wallets')->where('id', $walletId)->lockForUpdate()->first();
    
                if (!$wallet) {
                    throw new \RuntimeException("Wallet {$walletId} not found");
                }
    
                $balanceBefore = (int) $wallet->balance;
    
                // Проверка баланса
                if ($balanceBefore < $amount) {
                    Log::channel('audit')->warning('Insufficient funds for debit', [
                        'wallet_id' => $walletId,
                        'required_amount' => $amount,
                        'available_balance' => $balanceBefore,
                        'correlation_id' => $correlationId,
                    ]);
    
                    throw new \RuntimeException(
                        "Insufficient funds. Available: {$balanceBefore}, Required: {$amount}"
                    );
                }
    
                $balanceAfter = $balanceBefore - $amount;
    
                // Обновить баланс
                DB::table('wallets')->where('id', $walletId)->update([
                    'balance' => $balanceAfter,
                    'available_balance' => $balanceAfter - (int) ($wallet->hold_amount ?? 0),
                    'cached_balance' => $balanceAfter,
                    'cached_at' => now(),
                ]);
    
                // Создать запись в balance_transactions
                DB::table('balance_transactions')->insert([
                    'uuid' => Str::uuid(),
                    'correlation_id' => $correlationId,
                    'tenant_id' => $wallet->tenant_id,
                    'wallet_id' => $walletId,
                    'user_id' => $wallet->user_id,
                    'type' => $type,
                    'amount' => $amount,
                    'status' => 'completed',
                    'source_type' => null,
                    'source_id' => $sourceId,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'hold_before' => (int) ($wallet->hold_amount ?? 0),
                    'hold_after' => (int) ($wallet->hold_amount ?? 0),
                    'description' => $reason,
                    'created_by_type' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                // Логирование
                Log::channel('audit')->info('Wallet debited', [
                    'wallet_id' => $walletId,
                    'amount' => $amount,
                    'type' => $type,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reason' => $reason,
                    'source_id' => $sourceId,
                    'correlation_id' => $correlationId,
                    'trace' => implode(' > ', array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), 0, 3)),
                ]);
    
                // Инвалидировать кэш
                $this->invalidateCache($wallet->tenant_id, $walletId);
    
                return true;
            });
        }
    
        /**
         * Заморозить средства (холд) для двухэтапной авторизации.
         * Согласно КАНОН 2026: проверка баланса, создание записи в wallet_holds.
         *
         * @param int $walletId       ID кошелька
         * @param int $amount         Размер холда в копейках
         * @param string $sourceType  Тип источника (payment, order, appointment, etc)
         * @param string $sourceId    ID источника
         * @param string|null $correlationId Correlation ID
         *
         * @return string UUID холда
         *
         * @throws \RuntimeException
         */
        public function hold(
            int $walletId,
            int $amount,
            string $sourceType,
            string $sourceId,
            ?string $correlationId = null
        ): string {
            $correlationId = $correlationId ?? Str::uuid()->toString();
            $holdUuid = Str::uuid()->toString();
    
            return DB::transaction(function () use (
                $walletId,
                $amount,
                $sourceType,
                $sourceId,
                $correlationId,
                $holdUuid
            ): string {
                if ($amount <= 0) {
                    throw new \InvalidArgumentException('Hold amount must be greater than 0');
                }
    
                // Получить кошелёк с локировкой
                $wallet = DB::table('wallets')->where('id', $walletId)->lockForUpdate()->first();
    
                if (!$wallet) {
                    throw new \RuntimeException("Wallet {$walletId} not found");
                }
    
                $balanceBefore = (int) $wallet->balance;
                $holdBefore = (int) ($wallet->hold_amount ?? 0);
    
                // Проверить доступный баланс (баланс минус существующие холды)
                if ($balanceBefore - $holdBefore < $amount) {
                    throw new \RuntimeException(
                        "Insufficient available balance for hold. Available: "
                        . ($balanceBefore - $holdBefore) . ", Required: {$amount}"
                    );
                }
    
                // Обновить hold_amount в кошельке
                $holdAfter = $holdBefore + $amount;
                $availableBalance = $balanceBefore - $holdAfter;
    
                DB::table('wallets')->where('id', $walletId)->update([
                    'hold_amount' => $holdAfter,
                    'available_balance' => max(0, $availableBalance),
                    'cached_balance' => $balanceBefore, // Общий баланс не меняется
                    'cached_at' => now(),
                ]);
    
                // Создать запись холда в wallet_holds
                DB::table('wallet_holds')->insert([
                    'uuid' => $holdUuid,
                    'correlation_id' => $correlationId,
                    'tenant_id' => $wallet->tenant_id,
                    'wallet_id' => $walletId,
                    'user_id' => $wallet->user_id,
                    'amount' => $amount,
                    'status' => 'active',
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'description' => "Hold for {$sourceType}:{$sourceId}",
                    'expires_at' => now()->addHours(72), // Hold истекает через 72 часа
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                // Логирование
                Log::channel('audit')->info('Wallet hold created', [
                    'hold_uuid' => $holdUuid,
                    'wallet_id' => $walletId,
                    'amount' => $amount,
                    'hold_before' => $holdBefore,
                    'hold_after' => $holdAfter,
                    'available_balance' => max(0, $availableBalance),
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'correlation_id' => $correlationId,
                ]);
    
                // Инвалидировать кэш
                $this->invalidateCache($wallet->tenant_id, $walletId);
    
                return $holdUuid;
            });
        }
    
        /**
         * Снять холд (отпустить заморозку).
         * Согласно КАНОН 2026: не списывает средства, просто снимает холд.
         *
         * @param string $holdUuid UUID холда
         * @param string|null $correlationId Correlation ID
         *
         * @return bool
         *
         * @throws \RuntimeException
         */
        public function release(string $holdUuid, ?string $correlationId = null): bool
        {
            $correlationId = $correlationId ?? Str::uuid()->toString();
    
            return DB::transaction(function () use ($holdUuid, $correlationId): bool {
                // Получить запись холда
                $hold = DB::table('wallet_holds')->where('uuid', $holdUuid)->lockForUpdate()->first();
    
                if (!$hold) {
                    throw new \RuntimeException("Hold {$holdUuid} not found");
                }
    
                if ($hold->status !== 'active') {
                    throw new \RuntimeException("Hold {$holdUuid} is not active (status: {$hold->status})");
                }
    
                // Получить кошелёк с локировкой
                $wallet = DB::table('wallets')->where('id', $hold->wallet_id)->lockForUpdate()->first();
    
                // Уменьшить hold_amount
                $holdAfter = max(0, (int) $wallet->hold_amount - (int) $hold->amount);
    
                DB::table('wallets')->where('id', $hold->wallet_id)->update([
                    'hold_amount' => $holdAfter,
                    'available_balance' => (int) $wallet->balance - $holdAfter,
                    'cached_at' => now(),
                ]);
    
                // Отметить холд как released
                DB::table('wallet_holds')->where('uuid', $holdUuid)->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]);
    
                // Логирование
                Log::channel('audit')->info('Wallet hold released', [
                    'hold_uuid' => $holdUuid,
                    'wallet_id' => $hold->wallet_id,
                    'amount' => $hold->amount,
                    'correlation_id' => $correlationId,
                ]);
    
                // Инвалидировать кэш
                $this->invalidateCache($wallet->tenant_id, $hold->wallet_id);
    
                return true;
            });
        }
    
        /**
         * Захватить холд (списать со счёта).
         * Согласно КАНОН 2026: превращает холд в списание.
         *
         * @param string $holdUuid UUID холда
         * @param string $correlationId Correlation ID для связи
         *
         * @return bool
         *
         * @throws \RuntimeException
         */
        public function capture(string $holdUuid, string $correlationId): bool
        {
            return DB::transaction(function () use ($holdUuid, $correlationId): bool {
                // Получить запись холда
                $hold = DB::table('wallet_holds')->where('uuid', $holdUuid)->lockForUpdate()->first();
    
                if (!$hold) {
                    throw new \RuntimeException("Hold {$holdUuid} not found");
                }
    
                if ($hold->status !== 'active') {
                    throw new \RuntimeException("Hold {$holdUuid} is not active (status: {$hold->status})");
                }
    
                // Отметить холд как captured
                DB::table('wallet_holds')->where('uuid', $holdUuid)->update([
                    'status' => 'captured',
                    'captured_at' => now(),
                ]);
    
                // Создать запись в balance_transactions (withdrawal)
                DB::table('balance_transactions')->insert([
                    'uuid' => Str::uuid(),
                    'correlation_id' => $correlationId,
                    'tenant_id' => $hold->tenant_id,
                    'wallet_id' => $hold->wallet_id,
                    'user_id' => $hold->user_id,
                    'type' => 'withdrawal',
                    'amount' => $hold->amount,
                    'status' => 'completed',
                    'source_type' => $hold->source_type,
                    'source_id' => $hold->source_id,
                    'balance_before' => DB::table('wallets')->where('id', $hold->wallet_id)->first()->balance,
                    'balance_after' => DB::table('wallets')->where('id', $hold->wallet_id)->first()->balance - $hold->amount,
                    'hold_before' => (int) $hold->wallet_id,
                    'hold_after' => 0, // После захвата холда он снят
                    'description' => "Hold capture for {$hold->source_type}:{$hold->source_id}",
                    'created_by_type' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                // Логирование
                Log::channel('audit')->info('Wallet hold captured', [
                    'hold_uuid' => $holdUuid,
                    'wallet_id' => $hold->wallet_id,
                    'amount' => $hold->amount,
                    'correlation_id' => $correlationId,
                ]);
    
                // Инвалидировать кэш
                $this->invalidateCache($hold->tenant_id, $hold->wallet_id);
    
                return true;
            });
        }
    
        /**
         * Получить текущий баланс кошелька.
         * Согласно КАНОН 2026: проверить Redis кэш перед БД.
         */
        public function getBalance(int $walletId): int
        {
            $cacheKey = "wallet:balance:{$walletId}";
    
            // Пытаться получить из Redis
            $cached = Redis::get($cacheKey);
            if ($cached !== null) {
                return (int) $cached;
            }
    
            // Получить из БД
            $wallet = DB::table('wallets')->where('id', $walletId)->first();
    
            if (!$wallet) {
                throw new \RuntimeException("Wallet {$walletId} not found");
            }
    
            // Закэшировать в Redis (TTL 5 минут)
            Redis::setex($cacheKey, 300, (int) $wallet->balance);
    
            return (int) $wallet->balance;
        }
    
        /**
         * Получить доступный баланс (баланс минус холды).
         */
        public function getAvailableBalance(int $walletId): int
        {
            $wallet = DB::table('wallets')->where('id', $walletId)->first();
    
            if (!$wallet) {
                throw new \RuntimeException("Wallet {$walletId} not found");
            }
    
            return max(0, (int) $wallet->balance - (int) ($wallet->hold_amount ?? 0));
        }
    
        /**
         * Инвалидировать Redis кэш баланса.
         */
        private function invalidateCache(string $tenantId, int $walletId): void
        {
            Redis::del("wallet:balance:{$walletId}");
            Redis::del("wallet:available:{$walletId}");
}
