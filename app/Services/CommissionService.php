<?php declare(strict_types=1);

namespace App\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * Commission Service
 * Production 2026 CANON
 *
 * Тарифы комиссий:
 * B2C (физлица):   14% (все вертикали по умолчанию)
 * B2B (standard): 12%
 * B2B (silver):   11%
 * B2B (gold):     10%
 * B2B (platinum): 8%
 *
 * Миграционные скидки (4 месяца после перехода с dikidi, booking, етц.)
 * @author CatVRF Team
 * @version 2026.04.13
 */
final readonly class CommissionService
{
    public function __construct(
        private LogManager $logger,
        private DatabaseManager $db,
        private FraudControlService $fraud,
        private AuditService $audit,
    ) {}

    /**
     * Рассчитать комиссию для транзакции.
     *
     * @param int    $tenantId  ID тенанта
     * @param string $vertical  Вертикаль (beauty, food, hotels, auto, ...)
     * @param int    $amount    Сумма в копейках
     * @param array  $context   Доп. контекст: b2b_tier, migration_source, has_fleet
     * @return int Сумма комиссии в копейках
     */
    public function calculateCommission(
        int $tenantId,
        string $vertical,
        int $amount,
        array $context = []
    ): int {
        $baseRate = $this->getBaseRate($vertical);

        // B2B-тир снижает комиссию (8–12% вместо 14%)
        if (!empty($context['b2b_tier'])) {
            $baseRate = $this->getB2BTierRate($vertical, $context['b2b_tier']);
        }

        $rate = $baseRate / 100;

        // Миграционная скидка (4 месяца)
        if (!empty($context['migration_source'])) {
            $migrationRate = $this->getMigrationDiscount($vertical, $context['migration_source']);
            if ($migrationRate > 0) {
                $rate = $migrationRate / 100;
            }
        }

        $commission = (int) ($amount * $rate);

        // +5% для auto флита
        if ($vertical === 'auto' && !empty($context['has_fleet'])) {
            $commission += (int) ($amount * 0.05);
        }

        return $commission;
    }

    /**
     * Record commission (idempotent)
     *
     * @param int $tenantId Tenant ID
     * @param string $vertical Vertical type
     * @param int $amount Transaction amount
     * @param int $commission Commission amount
     * @param string $operationType Type of operation (payment, booking, order, etc.)
     * @param int $operationId Operation ID (payment_id, booking_id, etc.)
     * @param string $correlationId Tracing ID
     * @param array $context Additional context
     * @return int Commission record ID
     * @throws \Exception If commission already recorded (duplicate operation_id)
     */
    public function recordCommission(
        int $tenantId,
        string $vertical,
        int $amount,
        int $commission,
        string $operationType,
        int $operationId,
        string $correlationId,
        array $context = []
    ): int {
        $this->fraud->check(
            userId: $tenantId,
            operationType: 'commission_record',
            amount: $commission,
            correlationId: $correlationId,
        );
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
            // Check idempotency
            $existing = $this->db->table('commission_records')
                ->where('operation_type', $operationType)
                ->where('operation_id', $operationId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($existing) {
                throw new \RuntimeException(
                    "Commission already recorded for {$operationType}:{$operationId}. Record ID: {$existing->id}"
                );
            }

            // Determine payout schedule based on vertical
            $payoutScheduledFor = self::getPayoutSchedule($vertical);

            // Insert commission record
            $id = $this->db->table('commission_records')->insertGetId([
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'amount' => $amount,
                'commission' => $commission,
                'rate' => ($commission / $amount) * 100,
                'operation_type' => $operationType,
                'operation_id' => $operationId,
                'status' => 'pending',
                'payout_scheduled_for' => $payoutScheduledFor,
                'context' => json_encode($context),
                'correlation_id' => $correlationId,
                'recorded_at' => now(),
                'created_at' => now(),
            ]);

            // Запись в audit
            $this->audit->record('commission_recorded', 'commission_records', $id, [], [
                'tenant_id'            => $tenantId,
                'vertical'             => $vertical,
                'amount'               => $amount,
                'commission'           => $commission,
                'rate_percent'         => ($commission / $amount) * 100,
                'operation_type'       => $operationType,
                'operation_id'         => $operationId,
                'payout_scheduled_for' => $payoutScheduledFor,
            ], $correlationId);

            $this->logger->channel('audit')->info('Commission recorded', [
                'correlation_id' => $correlationId,
                'commission_id' => $id,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'amount' => $amount,
                'commission' => $commission,
                'rate_percent' => ($commission / $amount) * 100,
                'operation_type' => $operationType,
                'operation_id' => $operationId,
                'payout_scheduled_for' => $payoutScheduledFor,
            ]);

            return $id;
        });
    }

    /**
     * Get commission stats for tenant
     *
     * @param int $tenantId Tenant ID
     * @param string|null $vertical Filter by vertical (optional)
     * @param string $period Period: day, week, month, all
     * @return array Statistics
     */
    public function getCommissionStats(
        int $tenantId,
        ?string $vertical = null,
        string $period = 'month'
    ): array {
        $query = $this->db->table('commission_records')
            ->where('tenant_id', $tenantId);

        if ($vertical) {
            $query->where('vertical', $vertical);
        }

        // Filter by period
        match ($period) {
            'week' => $query->where('recorded_at', '>=', now()->subWeek()),
            'month' => $query->where('recorded_at', '>=', now()->subMonth()),
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
     * Базовая ставка комиссии B2C (14% по умолчанию).
     */
    private function getBaseRate(string $vertical): float
    {
        return match ($vertical) {
            'food'        => 14.0,
            'hotels'      => 14.0,
            'auto'        => 15.0,
            'real_estate' => 14.0,
            'courses'     => 14.0,
            'medical'     => 14.0,
            'pet'         => 14.0,
            'tickets'     => 12.0,
            'travel'      => 14.0,
            default       => 14.0,
        };
    }

    /**
     * B2B-тарифы комиссии по тиру (канон: 8–12%).
     *
     * B2B standard: 12%
     * B2B silver:   11%
     * B2B gold:     10%
     * B2B platinum: 8%
     */
    private function getB2BTierRate(string $vertical, string $tier): float
    {
        // auto и tickets остаются неизменными даже для B2B
        if (in_array($vertical, ['auto', 'tickets'], true)) {
            return $this->getBaseRate($vertical);
        }

        return match ($tier) {
            'platinum' => 8.0,
            'gold'     => 10.0,
            'silver'   => 11.0,
            default    => 12.0, // standard
        };
    }

    /**
     * Миграционная скидка (4 месяца после перехода с dikidi, booking и т.д.)
     */
    private function getMigrationDiscount(string $vertical, string $migrationSource): float
    {
        $discounts = [
            'beauty' => ['dikidi' => 10.0, 'flowwow' => 10.0],
            'hotels' => ['booking' => 12.0, 'ostrovok' => 12.0],
            'food'   => ['yandex_eats' => 12.0, 'delivery_club' => 12.0],
            'auto'   => ['yandex_taxi' => 14.0, 'uber' => 14.0],
        ];

        return $discounts[$vertical][$migrationSource] ?? 0.0;
    }

    /**
     * Get payout schedule based on vertical
     *
     * @param string $vertical Vertical type
     * @return \DateTime|null Payout date or null if immediate
     */
    private static function getPayoutSchedule(string $vertical): ?\DateTime
    {
        return match ($vertical) {
            'tickets' => now()->addDays(7), // 7-day payout
            'food' => now()->addDays(7), // 7-day payout
            'beauty' => now()->addDays(7), // 7-day payout
            'auto' => now()->addDays(1), // Daily payout for drivers
            default => now()->addDays(7), // 7-day default
        };
    }

    /**
     * Mark commission as paid
     *
     * @param int $commissionId Commission record ID
     * @param string $correlationId Tracing ID
     * @return bool
     */
    public function markAsPaid(int $commissionId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($commissionId, $correlationId) {
            $this->db->table('commission_records')
                ->where('id', $commissionId)
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

            $this->logger->channel('audit')->info('Commission marked as paid', [
                'correlation_id' => $correlationId,
                'commission_id' => $commissionId,
            ]);

            return true;
        });
    }

    /**
     * Get pending commissions for payout
     *
     * @param int $tenantId Tenant ID
     * @param string|null $vertical Filter by vertical (optional)
     * @return array Pending commission records
     */
    public function getPendingCommissions(int $tenantId, ?string $vertical = null): array
    {
        $query = $this->db->table('commission_records')
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('payout_scheduled_for', '<=', now());

        if ($vertical) {
            $query->where('vertical', $vertical);
        }

        return $query->orderBy('payout_scheduled_for')->get()->toArray();
    }
}
