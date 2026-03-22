<?php declare(strict_types=1);

namespace App\Services\Bonus;

use App\Models\BonusTransaction;
use App\Models\Wallet;
use App\Services\FraudControlService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class BonusService
{
    private const HOLD_PERIOD_DAYS = 14;

    public function __construct(
        private FraudControlService $fraudControl,
        private WalletService $walletService,
    ) {}

    /**
     * Начальное начисление бонусов (Cashback) с холдом
     */
    public function awardBonus(
        int $userId,
        int $tenantId,
        int $amount,
        string $type = 'loyalty',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $correlationId = null,
        array $meta = [],
    ): BonusTransaction {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        // Fraud check before award
        $fraudResult = $this->fraudControl->check(
            userId: $userId,
            operationType: "bonus_award_{$type}",
            amount: $amount,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Bonus award blocked by fraud check', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amount,
                'fraud_score' => $fraudResult['score'],
            ]);

            throw new \RuntimeException('Bonus award blocked by security system');
        }

        return DB::transaction(function () use (
            $userId, $tenantId, $amount, $type, $sourceType, $sourceId, $correlationId, $meta
        ) {
            $wallet = Wallet::where('tenant_id', $tenantId)->firstOrFail();

            $bonus = BonusTransaction::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'status' => BonusTransaction::STATUS_PENDING,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'hold_until' => now()->addDays(self::HOLD_PERIOD_DAYS),
                'meta' => $meta,
                'expires_at' => now()->addDays(365), // 1 year expiry by default
                'tags' => ['cashback' => true],
            ]);

            Log::channel('audit')->info('Bonus awarded (pending)', [
                'correlation_id' => $correlationId,
                'bonus_id' => $bonus->id,
                'user_id' => $userId,
                'amount' => $amount,
                'hold_until' => $bonus->hold_until,
            ]);

            return $bonus;
        });
    }

    /**
     * Разморозка бонусов по истечении периода охлаждения
     */
    public function unlockExpiredHolds(): int
    {
        $correlationId = Str::uuid()->toString();
        $unlockedCount = 0;

        $pendingBonuses = BonusTransaction::where('status', BonusTransaction::STATUS_PENDING)
            ->where('hold_until', '<=', now())
            ->limit(100)
            ->get();

        foreach ($pendingBonuses as $bonus) {
            try {
                DB::transaction(function () use ($bonus, $correlationId) {
                    $bonus->update([
                        'status' => BonusTransaction::STATUS_CREDITED,
                        'credited_at' => now(),
                    ]);

                    // Зачисляем на бонусный баланс кошелька (или в основной, если нет разделения)
                    // По канону 2026 бонусе зачисляются на wallet через WalletService::credit
                    $this->walletService->credit(
                        tenantId: $bonus->tenant_id,
                        amount: $bonus->amount,
                        type: 'bonus',
                        sourceId: $bonus->id,
                        correlationId: $correlationId,
                        reason: "Bonus unlock (type: {$bonus->type}, source: {$bonus->source_id})",
                        sourceType: 'bonus_transaction',
                        walletId: $bonus->wallet_id,
                    );
                });

                $unlockedCount++;
            } catch (\Exception $e) {
                Log::channel('audit')->error('Failed to unlock bonus', [
                    'bonus_id' => $bonus->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return $unlockedCount;
    }

    /**
     * Трата бонусов
     */
    public function spendBonuses(
        int $userId,
        int $tenantId,
        int $amount,
        string $reason,
        ?string $correlationId = null
    ): void {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        // 1. Проверяем доступный баланс бонусов
        // (Это делается через WalletService::debit с типом 'bonus')
        
        $this->walletService->debit(
            tenantId: $tenantId,
            amount: $amount,
            type: 'bonus',
            sourceId: null,
            correlationId: $correlationId,
            reason: $reason,
            sourceType: 'manual', // or checkout
        );

        Log::channel('audit')->info('Bonuses spent', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }
}
