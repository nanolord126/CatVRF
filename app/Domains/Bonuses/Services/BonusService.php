<?php declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\DTOs\AwardBonusDto;
use App\Domains\Bonuses\DTOs\SpendBonusDto;
use App\Domains\Bonuses\Models\BonusTransaction;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

final readonly class BonusService
{
    private const HOLD_PERIOD_DAYS = 14;
    private const EXPIRY_DAYS = 365;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Award bonus to user with hold period
     */
    public function award(AwardBonusDto $dto, string $correlationId): BonusTransaction
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check([
            'operation_type' => "bonus_award_{$dto->type}",
            'amount' => $dto->amount,
            'user_id' => $dto->userId,
            'bonus_type' => $dto->type,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $bonus = BonusTransaction::create([
                'tenant_id' => $dto->tenantId,
                'user_id' => $dto->userId,
                'type' => $dto->type,
                'amount' => $dto->amount,
                'status' => 'pending',
                'source_type' => $dto->sourceType,
                'source_id' => $dto->sourceId,
                'hold_until' => now()->addDays(self::HOLD_PERIOD_DAYS),
                'expires_at' => now()->addDays(self::EXPIRY_DAYS),
                'metadata' => $dto->metadata,
                'tags' => ['bonus', 'pending', $dto->type],
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'bonus_awarded',
                subjectType: BonusTransaction::class,
                subjectId: $bonus->id,
                newValues: $bonus->toArray(),
                correlationId: $correlationId,
            );

            return $bonus;
        });
    }

    /**
     * Unlock expired holds
     */
    public function unlockExpiredHolds(string $correlationId = ''): int
    {
        $correlationId ??= Str::uuid()->toString();
        $unlockedCount = 0;

        $pendingBonuses = BonusTransaction::pending()
            ->where('hold_until', '<=', now())
            ->limit(100)
            ->get();

        foreach ($pendingBonuses as $bonus) {
            try {
                $this->db->transaction(function () use ($bonus, $correlationId) {
                    $bonus->update([
                        'status' => 'credited',
                        'credited_at' => now(),
                    ]);

                    // Credit to wallet
                    $this->wallet->credit(
                        walletId: $bonus->wallet_id,
                        amount: $bonus->amount,
                        reason: "Bonus unlock (type: {$bonus->type})",
                        correlationId: $correlationId,
                    );

                    $this->audit->record(
                        action: 'bonus_unlocked',
                        subjectType: BonusTransaction::class,
                        subjectId: $bonus->id,
                        correlationId: $correlationId,
                    );
                });

                $unlockedCount++;
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error('Bonus unlock failed', [
                    'bonus_id' => $bonus->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return $unlockedCount;
    }

    /**
     * Spend bonuses
     */
    public function spend(SpendBonusDto $dto, string $correlationId): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check([
            'operation_type' => 'bonus_spend',
            'amount' => $dto->amount,
            'user_id' => $dto->userId,
            'reason' => $dto->reason,
            'correlation_id' => $correlationId,
        ]);

        // Debit from wallet (bonuses are stored in wallet)
        $this->db->transaction(function () use ($dto, $correlationId) {
            $wallet = \App\Models\Wallet::where('tenant_id', $dto->tenantId)
                ->whereHas('user', fn($q) => $q->where('id', $dto->userId))
                ->firstOrFail();

            $this->wallet->debit(
                walletId: $wallet->id,
                amount: $dto->amount,
                reason: "Bonus spent: {$dto->reason}",
                correlationId: $correlationId,
            );

            $this->audit->record(
                action: 'bonus_spent',
                subjectType: BonusTransaction::class,
                subjectId: null,
                newValues: [
                    'user_id' => $dto->userId,
                    'amount' => $dto->amount,
                    'reason' => $dto->reason,
                ],
                correlationId: $correlationId,
            );
        });
    }

    /**
     * Get available bonus balance
     */
    public function getAvailableBalance(int $tenantId, int $userId): int
    {
        return BonusTransaction::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->available()
            ->sum('amount');
    }

    /**
     * Get bonus history
     */
    public function getHistory(int $tenantId, int $userId, int $perPage = 20)
    {
        return BonusTransaction::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Expire old bonuses
     */
    public function expireOldBonuses(string $correlationId = ''): int
    {
        $correlationId ??= Str::uuid()->toString();

        $expiredCount = BonusTransaction::credited()
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'expired',
            ]);

        $this->audit->record(
            action: 'bonuses_expired',
            subjectType: BonusTransaction::class,
            subjectId: null,
            newValues: ['expired_count' => $expiredCount],
            correlationId: $correlationId,
        );

        return $expiredCount;
    }
}
