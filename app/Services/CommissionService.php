<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Commission Service
 * Production 2026 CANON
 *
 * Calculates and tracks commissions per-vertical:
 * - Beauty: 14% / 10-12% with migration
 * - Food: 14%
 * - Hotels: 12-14% (4-day payout)
 * - Auto: 15% + 5% fleet owner
 * - RealEstate: 14%
 *
 * Features:
 * - Idempotent commission recording (operation_id prevents duplicates)
 * - Atomic DB transactions
 * - Full audit trail with correlation_id
 * - Per-vertical rate configuration
 *
 * @author CatVRF Team
 * @version 2026.03.24
 */
final class CommissionService
{
    /**
     * Calculate commission for transaction
     *
     * @param int $tenantId Tenant ID
     * @param string $vertical Vertical type (beauty, food, hotels, auto, etc.)
     * @param int $amount Amount in kopeks
     * @param array $context Additional context (migration_source, has_fleet, etc.)
     * @return int Commission amount in kopeks
     */
    public static function calculateCommission(
        int $tenantId,
        string $vertical,
        int $amount,
        array $context = []
    ): int {
        $baseRate = self::getBaseRate($vertical);
        $rate = $baseRate / 100;

        // Apply migration discount if applicable
        if (!empty($context['migration_source'])) {
            $migrationDiscount = self::getMigrationDiscount($vertical, $context['migration_source']);
            if ($migrationDiscount > 0) {
                $rate = $migrationDiscount / 100;
            }
        }

        // Calculate commission
        $commission = (int)($amount * $rate);

        // Additional rules per vertical
        if ($vertical === 'auto' && !empty($context['has_fleet'])) {
            // Add 5% for fleet owner
            $commission += (int)($amount * 0.05);
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
    public static function recordCommission(
        int $tenantId,
        string $vertical,
        int $amount,
        int $commission,
        string $operationType,
        int $operationId,
        string $correlationId,
        array $context = []
    ): int {
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
                throw new \Exception(
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

            // Schedule payout
            if ($payoutScheduledFor) {
                SchedulerService::schedulePayout(
                    $id,
                    'commission_records',
                    $commission,
                    $payoutScheduledFor,
                    $correlationId
                );
            }

            $this->log->channel('audit')->info('Commission recorded', [
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
    public static function getCommissionStats(
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
            'day' => $query->where('recorded_at', '>=', now()->subDay()),
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
     * Get base commission rate for vertical
     *
     * @param string $vertical Vertical type
     * @return float Base rate (as percentage: 14 = 14%)
     */
    private static function getBaseRate(string $vertical): float
    {
        return match ($vertical) {
            'beauty' => 14.0,
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

    /**
     * Get migration discount for vertical + platform
     *
     * @param string $vertical Vertical type
     * @param string $migrationSource Source platform (dikidi, booking, ostrovok, etc.)
     * @return float Discounted rate (0 = no discount)
     */
    private static function getMigrationDiscount(string $vertical, string $migrationSource): float
    {
        $discounts = [
            'beauty' => [
                'dikidi' => 10.0, // 10% first 4 months → 12% after
                'flowwow' => 10.0,
            ],
            'hotels' => [
                'booking' => 12.0,
                'ostrovok' => 12.0,
            ],
            'food' => [
                'yandex_eats' => 12.0,
                'delivery_club' => 12.0,
            ],
            'auto' => [
                'yandex_taxi' => 14.0, // No discount for taxis
                'uber' => 14.0,
            ],
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
            'hotels' => now()->addDays(4), // 4-day payout
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
    public static function markAsPaid(int $commissionId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($commissionId, $correlationId) {
            $this->db->table('commission_records')
                ->where('id', $commissionId)
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

            $this->log->channel('audit')->info('Commission marked as paid', [
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
    public static function getPendingCommissions(int $tenantId, ?string $vertical = null): array
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
