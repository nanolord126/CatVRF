<?php declare(strict_types=1);

namespace App\Services\Bonus;

use App\Models\BonusTransaction;
use App\Models\Wallet;
use App\Services\FraudControlService;
use App\Services\Wallet\WalletService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Сервис управления бонусами (Bonus Service)
 *
 * КАНОН 2026 - Production Ready
 * Управление начислением, холдом, разморозкой и тратой бонусов
 *
 * Требования:
 * 1. FraudControlService::check() перед каждой выдачей бонусов
 * 2. DB::transaction() для атомарности
 * 3. correlation_id для трейсирования
 * 4. Log::channel('audit') для всех операций
 * 5. Exception handling с полным backtrace
 * 6. Бонусы имеют период холда (14 дней по умолчанию)
 * 7. После холда → зачисляются на wallet через WalletService
 * 8. Физлицо может только тратить бонусы, бизнес может выводить
 */
final readonly class BonusService
{
    private const HOLD_PERIOD_DAYS = 14;

    public function __construct(
        private ConnectionInterface $db,
        private LogManager $log,
        private FraudControlService $fraud,
        private WalletService $wallet,
    ) {}

    /**
     * Начислить бонусы пользователю (с холдом)
     *
     * Бонусы идут в статус PENDING на 14 дней, потом → CREDITED
     * После CREDITED → зачисляются на основной баланс через WalletService
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $amount (копейки)
     * @param string $type (loyalty, referral, turnover, promo, migration)
     * @param ?string $sourceType
     * @param ?int $sourceId
     * @param ?string $correlationId
     * @param array $metadata
     * @return BonusTransaction
     *
     * @throws RuntimeException (fraud check failed)
     * @throws Throwable
     */
    public function awardBonus(
        int $userId,
        int $tenantId,
        int $amount,
        string $type = 'loyalty',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $correlationId = null,
        array $metadata = [],
    ): BonusTransaction {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK before award
            $this->fraud->check([
                'operation_type' => "bonus_award_{$type}",
                'amount' => $amount,
                'user_id' => $userId,
                'bonus_type' => $type,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Bonus: Award initiated', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type,
            ]);

            // 2. DATABASE TRANSACTION
            $bonus = DB::transaction(function () use (
                $userId, $tenantId, $amount, $type, $sourceType, $sourceId, $correlationId, $metadata
            ) {
                $wallet = Wallet::where('tenant_id', $tenantId)
                    ->firstOrFail();

                return BonusTransaction::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => $tenantId,
                    'wallet_id' => $wallet->id,
                    'user_id' => $userId,
                    'type' => $type,
                    'amount' => $amount,
                    'status' => 'pending',  // Hold for 14 days
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'correlation_id' => $correlationId,
                    'hold_until' => now()->addDays(self::HOLD_PERIOD_DAYS),
                    'metadata' => $metadata,
                    'expires_at' => now()->addDays(365), // 1 year expiry
                    'tags' => ['bonus', 'pending', $type],
                ]);
            });

            // 3. SUCCESS LOG
            Log::channel('audit')->info('Bonus: Award succeeded', [
                'correlation_id' => $correlationId,
                'bonus_id' => $bonus->id,
                'user_id' => $userId,
                'amount' => $amount,
                'hold_until' => $bonus->hold_until,
            ]);

            return $bonus;
        } catch (\Exception $e) {
            // 4. ERROR LOG
            Log::channel('audit')->error('Bonus: Award failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Разморозить истёкшие бонусы (автоматически выполняется Job'ом)
     *
     * Переводит bonuses из статуса PENDING → CREDITED
     * Затем зачисляет их на основной баланс через WalletService
     *
     * @return int (количество разморозленных)
     */
    public function unlockExpiredHolds(): int
    {
        $correlationId = Str::uuid()->toString();
        $unlockedCount = 0;

        try {
            Log::channel('audit')->info('Bonus: Unlock expired holds started', [
                'correlation_id' => $correlationId,
            ]);

            $pendingBonuses = BonusTransaction::where('status', 'pending')
                ->where('hold_until', '<=', now())
                ->limit(100)
                ->get();

            foreach ($pendingBonuses as $bonus) {
                try {
                    DB::transaction(function () use ($bonus, $correlationId) {
                        // 1. UPDATE status
                        $bonus->update([
                            'status' => 'credited',
                            'credited_at' => now(),
                            'correlation_id' => $correlationId,
                        ]);

                        // 2. CREDIT на wallet
                        $this->wallet->deposit(
                            userId: $bonus->user_id,
                            tenantId: $bonus->tenant_id,
                            amountCents: $bonus->amount,
                            description: "Bonus unlock (type: {$bonus->type})",
                            correlationId: $correlationId,
                            metadata: [
                                'bonus_id' => $bonus->id,
                                'bonus_type' => $bonus->type,
                            ],
                        );

                        // 3. LOG
                        Log::channel('audit')->info('Bonus: Hold unlocked', [
                            'correlation_id' => $correlationId,
                            'bonus_id' => $bonus->id,
                            'amount' => $bonus->amount,
                        ]);
                    });

                    $unlockedCount++;
                } catch (\Exception $e) {
                    Log::channel('audit')->error('Bonus: Unlock failed', [
                        'correlation_id' => $correlationId,
                        'bonus_id' => $bonus->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::channel('audit')->info('Bonus: Unlock expired holds completed', [
                'correlation_id' => $correlationId,
                'unlocked_count' => $unlockedCount,
            ]);

            return $unlockedCount;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Bonus: Unlock process failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 0;
        }
    }

    /**
     * Потратить бонусы пользователем (в checkout)
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $amount (копейки)
     * @param string $reason (checkout, order, purchase)
     * @param ?string $correlationId
     * @return void
     *
     * @throws Throwable
     */
    public function spendBonuses(
        int $userId,
        int $tenantId,
        int $amount,
        string $reason = 'checkout',
        ?string $correlationId = null,
    ): void {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK on spending
            $this->fraud->check([
                'operation_type' => 'bonus_spend',
                'amount' => $amount,
                'user_id' => $userId,
                'reason' => $reason,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Bonus: Spending initiated', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            // 2. DEBIT from wallet
            $this->wallet->withdraw(
                userId: $userId,
                tenantId: $tenantId,
                amountCents: $amount,
                description: "Bonus spent: {$reason}",
                correlationId: $correlationId,
            );

            // 3. SUCCESS LOG
            Log::channel('audit')->info('Bonus: Spending succeeded', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            // 4. ERROR LOG
            Log::channel('audit')->error('Bonus: Spending failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить доступный баланс бонусов пользователя
     *
     * @param int $userId
     * @param int $tenantId
     * @return int (копейки)
     */
    public function getAvailableBonusBalance(int $userId, int $tenantId): int
    {
        return BonusTransaction::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'credited')
            ->where('expires_at', '>', now())
            ->sum('amount');
    }

    /**
     * Получить историю бонусных транзакций
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $perPage
     * @return \Illuminate\Pagination\Paginator
     */
    public function getHistory(int $userId, int $tenantId, int $perPage = 20): \Illuminate\Pagination\Paginator
    {
        return BonusTransaction::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Истечение бонусов (по дате expires_at)
     *
     * Выполняется автоматически Job'ом ежедневно
     *
     * @return int (количество истёкших)
     */
    public function expireOldBonuses(): int
    {
        $correlationId = Str::uuid()->toString();

        try {
            $expiredCount = BonusTransaction::where('expires_at', '<', now())
                ->where('status', 'credited')
                ->update([
                    'status' => 'expired',
                    'correlation_id' => $correlationId,
                ]);

            Log::channel('audit')->info('Bonus: Expired bonuses processed', [
                'correlation_id' => $correlationId,
                'expired_count' => $expiredCount,
            ]);

            return $expiredCount;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Bonus: Expiry process failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
