<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Labor Service
 *
 * Manages warehouse labor tracking:
 * - Track labor hours
 * - Calculate labor productivity
 * - Labor cost analysis
 * - Worker performance metrics
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryLaborService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Record labor time
     *
     * @param  int  $userId  User ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $activityType  Activity type
     * @param  string  $activityDescription  Activity description
     * @param  int  $minutes  Minutes worked
     * @param  int|null  $relatedEntityId  Related entity ID
     * @param  string|null  $relatedEntityType  Related entity type
     * @param  int  $tenantId  Tenant ID
     * @return int Labor record ID
     */
    public function recordLaborTime(
        int $userId,
        int $warehouseId,
        string $activityType,
        string $activityDescription,
        int $minutes,
        ?int $relatedEntityId,
        ?string $relatedEntityType,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        $laborId = $this->db->table('inventory_labor_records')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $userId,
            'warehouse_id' => $warehouseId,
            'activity_type' => $activityType,
            'activity_description' => $activityDescription,
            'minutes_worked' => $minutes,
            'related_entity_id' => $relatedEntityId,
            'related_entity_type' => $relatedEntityType,
            'tenant_id' => $tenantId,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $this->logAction(
            action: 'labor_time_recorded',
            entityType: 'InventoryLabor',
            entityId: $laborId,
            context: [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'activity_type' => $activityType,
                'minutes' => $minutes,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $laborId;
    }

    /**
     * Start labor session
     *
     * @param  int  $userId  User ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $activityType  Activity type
     * @param  string  $activityDescription  Activity description
     * @param  int  $tenantId  Tenant ID
     * @return int Session ID
     */
    public function startLaborSession(
        int $userId,
        int $warehouseId,
        string $activityType,
        string $activityDescription,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        $sessionId = $this->db->table('inventory_labor_sessions')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $userId,
            'warehouse_id' => $warehouseId,
            'activity_type' => $activityType,
            'activity_description' => $activityDescription,
            'status' => 'active',
            'tenant_id' => $tenantId,
            'started_at' => now(),
            'created_at' => now(),
        ]);

        $this->logAction(
            action: 'labor_session_started',
            entityType: 'InventoryLaborSession',
            entityId: $sessionId,
            context: [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'activity_type' => $activityType,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $sessionId;
    }

    /**
     * End labor session
     *
     * @param  int  $sessionId  Session ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Session result
     */
    public function endLaborSession(int $sessionId, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($sessionId, $userId, $tenantId, $correlationId) {
            $session = $this->db->table('inventory_labor_sessions')
                ->where('id', $sessionId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw new \RuntimeException("Active labor session {$sessionId} not found");
            }

            $endedAt = now();
            $minutesWorked = $session->started_at->diffInMinutes($endedAt);

            $this->db->table('inventory_labor_sessions')
                ->where('id', $sessionId)
                ->update([
                    'status' => 'completed',
                    'ended_at' => $endedAt,
                    'minutes_worked' => $minutesWorked,
                ]);

            $this->db->table('inventory_labor_records')->insert([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $session->user_id,
                'warehouse_id' => $session->warehouse_id,
                'activity_type' => $session->activity_type,
                'activity_description' => $session->activity_description,
                'minutes_worked' => $minutesWorked,
                'related_entity_id' => $sessionId,
                'related_entity_type' => 'labor_session',
                'tenant_id' => $tenantId,
                'recorded_at' => $endedAt,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'labor_session_ended',
                entityType: 'InventoryLaborSession',
                entityId: $sessionId,
                context: [
                    'correlation_id' => $correlationId,
                    'minutes_worked' => $minutesWorked,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'session_id' => $sessionId,
                'minutes_worked' => $minutesWorked,
                'started_at' => $session->started_at->toIso8601String(),
                'ended_at' => $endedAt->toIso8601String(),
            ];
        });
    }

    /**
     * Get labor productivity metrics
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Number of days (default: 30)
     * @return array Productivity metrics
     */
    public function getLaborProductivity(int $warehouseId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();

        $laborRecords = $this->db->table('inventory_labor_records')
            ->where('warehouse_id', $warehouseId)
            ->where('recorded_at', '>=', $startDate)
            ->get();

        $totalMinutes = $laborRecords->sum('minutes_worked');
        $totalHours = $totalMinutes / 60;

        $productivityByActivity = $laborRecords->groupBy('activity_type')
            ->map(function ($records) {
                $totalMinutes = $records->sum('minutes_worked');
                $recordCount = $records->count();

                return [
                    'total_minutes' => $totalMinutes,
                    'total_hours' => round($totalMinutes / 60, 2),
                    'record_count' => $recordCount,
                    'avg_minutes_per_record' => round($totalMinutes / $recordCount, 2),
                ];
            })->toArray();

        $productivityByUser = $laborRecords->groupBy('user_id')
            ->map(function ($records) {
                $totalMinutes = $records->sum('minutes_worked');
                $recordCount = $records->count();

                return [
                    'user_id' => $records->first()->user_id,
                    'total_minutes' => $totalMinutes,
                    'total_hours' => round($totalMinutes / 60, 2),
                    'record_count' => $recordCount,
                ];
            })->sortByDesc('total_minutes')->values()->toArray();

        return [
            'warehouse_id' => $warehouseId,
            'period_days' => $days,
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalHours, 2),
            'avg_hours_per_day' => round($totalHours / $days, 2),
            'productivity_by_activity' => $productivityByActivity,
            'productivity_by_user' => array_slice($productivityByUser, 0, 20),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate labor cost
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Number of days (default: 30)
     * @param  float  $hourlyRate  Average hourly rate
     * @return array Cost analysis
     */
    public function calculateLaborCost(int $warehouseId, int $days = 30, float $hourlyRate = 15.0): array
    {
        $startDate = now()->subDays($days)->toDateString();

        $laborRecords = $this->db->table('inventory_labor_records')
            ->where('warehouse_id', $warehouseId)
            ->where('recorded_at', '>=', $startDate)
            ->get();

        $totalMinutes = $laborRecords->sum('minutes_worked');
        $totalHours = $totalMinutes / 60;
        $totalCost = $totalHours * $hourlyRate;

        $costByActivity = $laborRecords->groupBy('activity_type')
            ->map(function ($records) use ($hourlyRate) {
                $minutes = $records->sum('minutes_worked');
                $hours = $minutes / 60;

                return [
                    'total_minutes' => $minutes,
                    'total_hours' => round($hours, 2),
                    'cost' => round($hours * $hourlyRate, 2),
                ];
            })->toArray();

        return [
            'warehouse_id' => $warehouseId,
            'period_days' => $days,
            'hourly_rate' => $hourlyRate,
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalHours, 2),
            'total_cost' => round($totalCost, 2),
            'avg_daily_cost' => round($totalCost / $days, 2),
            'cost_by_activity' => $costByActivity,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get worker performance metrics
     *
     * @param  int  $userId  User ID
     * @param  int  $days  Number of days (default: 30)
     * @return array Performance metrics
     */
    public function getWorkerPerformance(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();

        $laborRecords = $this->db->table('inventory_labor_records')
            ->where('user_id', $userId)
            ->where('recorded_at', '>=', $startDate)
            ->get();

        $totalMinutes = $laborRecords->sum('minutes_worked');
        $totalHours = $totalMinutes / 60;

        $activityBreakdown = $laborRecords->groupBy('activity_type')
            ->map(function ($records) {
                return [
                    'count' => $records->count(),
                    'total_minutes' => $records->sum('minutes_worked'),
                    'percentage' => 0,
                ];
            })->toArray();

        $totalActivities = array_sum(array_column($activityBreakdown, 'count'));

        foreach ($activityBreakdown as &$activity) {
            $activity['percentage'] = $totalActivities > 0
                ? round(($activity['count'] / $totalActivities) * 100, 2)
                : 0;
        }

        $avgDailyMinutes = $totalMinutes / $days;

        $pickListsCompleted = $this->db->table('inventory_pick_lists')
            ->where('picker_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->count();

        $packListsCompleted = $this->db->table('inventory_pack_lists')
            ->where('packer_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->count();

        return [
            'user_id' => $userId,
            'period_days' => $days,
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalHours, 2),
            'avg_daily_hours' => round($avgDailyMinutes / 60, 2),
            'activity_breakdown' => $activityBreakdown,
            'pick_lists_completed' => $pickListsCompleted,
            'pack_lists_completed' => $packListsCompleted,
            'picks_per_hour' => $totalHours > 0 ? round($pickListsCompleted / $totalHours, 2) : 0,
            'packs_per_hour' => $totalHours > 0 ? round($packListsCompleted / $totalHours, 2) : 0,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get active sessions
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Active sessions
     */
    public function getActiveSessions(int $warehouseId): array
    {
        $sessions = $this->db->table('inventory_labor_sessions')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->get();

        return $sessions->map(fn ($s) => [
            'session_id' => $s->id,
            'user_id' => $s->user_id,
            'activity_type' => $s->activity_type,
            'activity_description' => $s->activity_description,
            'started_at' => $s->started_at->toIso8601String(),
            'duration_minutes' => $s->started_at->diffInMinutes(now()),
        ])->toArray();
    }
}
