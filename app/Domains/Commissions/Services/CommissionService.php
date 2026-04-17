<?php declare(strict_types=1);

namespace App\Domains\Commissions\Services;

use App\Domains\Commissions\DTOs\CalculateCommissionDto;
use App\Domains\Commissions\Models\CommissionRecord;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final readonly class CommissionService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Calculate commission for transaction
     */
    public function calculate(CalculateCommissionDto $dto): int
    {
        $baseRate = $this->getBaseRate($dto->vertical);

        // B2B tier reduces commission
        if ($dto->b2bTier) {
            $baseRate = $this->getB2BTierRate($dto->vertical, $dto->b2bTier);
        }

        $rate = $baseRate / 100;

        // Migration discount (4 months)
        if ($dto->migrationSource) {
            $migrationRate = $this->getMigrationDiscount($dto->vertical, $dto->migrationSource);
            if ($migrationRate > 0) {
                $rate = $migrationRate / 100;
            }
        }

        $commission = (int) ($dto->amount * $rate);

        // +5% for auto fleet
        if ($dto->vertical === 'auto' && $dto->hasFleet) {
            $commission += (int) ($dto->amount * 0.05);
        }

        return $commission;
    }

    /**
     * Record commission (idempotent)
     */
    public function record(
        int $tenantId,
        string $vertical,
        int $amount,
        int $commission,
        string $operationType,
        int $operationId,
        string $correlationId,
        array $context = []
    ): int {
        $this->fraud->check([
            'operation_type' => 'commission_record',
            'amount' => $commission,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use (
            $tenantId,
            $vertical,
            $amount,
            $commission,
            $operationType,
            $operationId,
            $correlationId,
            $context
        ) {
            $existing = CommissionRecord::where('operation_type', $operationType)
                ->where('operation_id', $operationId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($existing) {
                $this->logger->channel('audit')->warning('Commission already recorded', [
                    'operation_type' => $operationType,
                    'operation_id' => $operationId,
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]);
                return $existing->id;
            }

            $payoutScheduledFor = $this->getPayoutSchedule($vertical);

            $record = CommissionRecord::create([
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'amount' => $amount,
                'commission' => $commission,
                'rate' => $amount > 0 ? ($commission / $amount) * 100 : 0,
                'operation_type' => $operationType,
                'operation_id' => $operationId,
                'status' => 'pending',
                'payout_scheduled_for' => $payoutScheduledFor,
                'context' => $context,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'commission_recorded',
                subjectType: CommissionRecord::class,
                subjectId: $record->id,
                newValues: $record->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('Commission recorded successfully', [
                'commission_id' => $record->id,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'amount' => $amount,
                'commission' => $commission,
                'correlation_id' => $correlationId,
            ]);

            return $record->id;
        });
    }

    /**
     * Get commission stats for tenant
     */
    public function getStats(int $tenantId, ?string $vertical = null, string $period = 'month'): array
    {
        $query = CommissionRecord::where('tenant_id', $tenantId);

        if ($vertical) {
            $query->where('vertical', $vertical);
        }

        match ($period) {
            'week' => $query->where('created_at', '>=', now()->subWeek()),
            'month' => $query->where('created_at', '>=', now()->subMonth()),
            default => null,
        };

        $records = $query->get();

        return [
            'total_amount' => $records->sum('amount'),
            'total_commission' => $records->sum('commission'),
            'average_rate' => $records->count() > 0
                ? ($records->sum('commission') / $records->sum('amount')) * 100
                : 0,
            'pending' => $records->where('status', 'pending')->count(),
            'paid' => $records->where('status', 'paid')->count(),
            'by_vertical' => $records->groupBy('vertical')->map(fn ($group) => [
                'amount' => $group->sum('amount'),
                'commission' => $group->sum('commission'),
                'count' => $group->count(),
            ])->toArray(),
        ];
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(int $commissionId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($commissionId, $correlationId) {
            $updated = CommissionRecord::where('id', $commissionId)
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

            if ($updated) {
                $this->audit->record(
                    action: 'commission_paid',
                    subjectType: CommissionRecord::class,
                    subjectId: $commissionId,
                    correlationId: $correlationId,
                );
            }

            return $updated > 0;
        });
    }

    /**
     * Get pending commissions for payout
     */
    public function getPending(int $tenantId, ?string $vertical = null): array
    {
        $query = CommissionRecord::where('tenant_id', $tenantId)
            ->dueForPayout();

        if ($vertical) {
            $query->where('vertical', $vertical);
        }

        return $query->orderBy('payout_scheduled_for')->get()->toArray();
    }

    private function getBaseRate(string $vertical): float
    {
        return match ($vertical) {
            'food' => 14.0,
            'hotels' => 14.0,
            'auto' => 15.0,
            'real_estate' => 14.0,
            'courses' => 14.0,
            'medical' => 14.0,
            'pet' => 14.0,
            'tickets' => 12.0,
            'travel' => 14.0,
            default => 14.0,
        };
    }

    private function getB2BTierRate(string $vertical, string $tier): float
    {
        if (in_array($vertical, ['auto', 'tickets'], true)) {
            return $this->getBaseRate($vertical);
        }

        return match ($tier) {
            'platinum' => 8.0,
            'gold' => 10.0,
            'silver' => 11.0,
            default => 12.0,
        };
    }

    private function getMigrationDiscount(string $vertical, string $migrationSource): float
    {
        $discounts = [
            'beauty' => ['dikidi' => 10.0, 'flowwow' => 10.0],
            'hotels' => ['booking' => 12.0, 'ostrovok' => 12.0],
            'food' => ['yandex_eats' => 12.0, 'delivery_club' => 12.0],
            'auto' => ['yandex_taxi' => 14.0, 'uber' => 14.0],
        ];

        return $discounts[$vertical][$migrationSource] ?? 0.0;
    }

    private function getPayoutSchedule(string $vertical): \DateTime
    {
        return match ($vertical) {
            'tickets' => now()->addDays(7),
            'food' => now()->addDays(7),
            'beauty' => now()->addDays(7),
            'auto' => now()->addDays(1),
            default => now()->addDays(7),
        };
    }
}
