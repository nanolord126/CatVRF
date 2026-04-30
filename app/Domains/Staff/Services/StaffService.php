<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffService — бизнес-логика управления сотрудниками.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: CRUD, архивация, статистика эффективности,
 * обновление KPI, расчёт выручки, управление ролями.
 */
final readonly class StaffService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard
    ) {}

    /**
     * Создание сотрудника с fraud-check и audit.
     */
    public function create(array $data): Staff
    {
        $correlationId = (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_create',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($data, $correlationId, $userId): Staff {
            $record = Staff::create(array_merge($data, [
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()?->id ?? $data['tenant_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'salary' => ($data['salary'] ?? 0) * 100, // В копейках
            ]));

            $this->logger->info('Staff record created', [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'correlation_id' => $correlationId,
                'tenant_id' => $record->tenant_id,
            ]);

            $this->logCreated(
                entityType: 'Staff',
                entityId: $record->id,
                context: [
                    'uuid' => $record->uuid,
                    'full_name' => $record->full_name,
                    'role' => $record->role,
                    'position' => $record->position,
                    'tenant_id' => $record->tenant_id,
                    'user_id' => $userId,
                ]
            );

            return $record;
        });
    }

    /**
     * Обновление сотрудника с fraud-check и audit.
     */
    public function update(Staff $record, array $data): Staff
    {
        $correlationId = (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_update',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($record, $data, $correlationId): Staff {
            $old = $record->toArray();
            
            if (isset($data['salary']) && $data['salary'] < 10000) {
                $data['salary'] = $data['salary'] * 100;
            }

            $record->update($data);

            $this->logger->info('Staff record updated', [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'correlation_id' => $correlationId,
            ]);

            $this->logUpdated(
                entityType: 'Staff',
                entityId: $record->id,
                oldData: $old,
                newData: $record->fresh()->toArray()
            );

            return $record->fresh();
        });
    }

    /**
     * Архивирование сотрудника.
     */
    public function archive(Staff $record, string $reason = ''): bool
    {
        $correlationId = (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_archive',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($record, $reason, $correlationId): bool {
            $record->archive($reason);

            $this->logger->info('Staff record archived', [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->logAction(
                action: 'archived',
                entityType: 'Staff',
                entityId: $record->id,
                context: [
                    'reason' => $reason,
                    'full_name' => $record->full_name,
                ]
            );

            return true;
        });
    }

    /**
     * Восстановление из архива.
     */
    public function restore(Staff $record): bool
    {
        $correlationId = (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_restore',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($record, $correlationId): bool {
            $record->restoreFromArchive();

            $this->logger->info('Staff record restored from archive', [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'correlation_id' => $correlationId,
            ]);

            $this->logAction(
                action: 'restored',
                entityType: 'Staff',
                entityId: $record->id,
                context: [
                    'full_name' => $record->full_name,
                ]
            );

            return true;
        });
    }

    /**
     * Обновление статистики сотрудника.
     */
    public function updateStats(Staff $record, array $stats): Staff
    {
        $correlationId = (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_update_stats',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($record, $stats, $correlationId): Staff {
            $updateData = [];
            
            if (isset($stats['orders_processed'])) {
                $updateData['total_orders_processed'] = $record->total_orders_processed + $stats['orders_processed'];
            }
            
            if (isset($stats['revenue'])) {
                $updateData['total_revenue_generated'] = $record->total_revenue_generated + ($stats['revenue'] * 100);
                $updateData['average_order_value'] = $updateData['total_orders_processed'] > 0
                    ? $updateData['total_revenue_generated'] / $updateData['total_orders_processed']
                    : 0;
            }
            
            if (isset($stats['customers_served'])) {
                $updateData['total_customers_served'] = $record->total_customers_served + $stats['customers_served'];
            }
            
            if (isset($stats['satisfaction_score'])) {
                $updateData['customer_satisfaction_score'] = $stats['satisfaction_score'];
            }
            
            if (isset($stats['positive_reviews'])) {
                $updateData['positive_reviews'] = $record->positive_reviews + $stats['positive_reviews'];
            }
            
            if (isset($stats['negative_reviews'])) {
                $updateData['negative_reviews'] = $record->negative_reviews + $stats['negative_reviews'];
            }
            
            if (isset($stats['complaints'])) {
                $updateData['complaints'] = $record->complaints + $stats['complaints'];
            }
            
            if (isset($stats['compliments'])) {
                $updateData['compliments'] = $record->compliments + $stats['compliments'];
            }
            
            if (isset($stats['tasks_completed'])) {
                $updateData['tasks_completed'] = $record->tasks_completed + $stats['tasks_completed'];
            }
            
            if (isset($stats['tasks_overdue'])) {
                $updateData['tasks_overdue'] = $record->tasks_overdue + $stats['tasks_overdue'];
            }
            
            if (isset($updateData['tasks_completed'])) {
                $totalTasks = $updateData['tasks_completed'] + ($updateData['tasks_overdue'] ?? $record->tasks_overdue);
                $updateData['task_completion_rate'] = $totalTasks > 0 ? ($updateData['tasks_completed'] / $totalTasks) * 100 : 0;
            }

            $record->update($updateData);

            $this->logger->info('Staff stats updated', [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'stats' => $stats,
                'correlation_id' => $correlationId,
            ]);

            $this->logAction(
                action: 'stats_updated',
                entityType: 'Staff',
                entityId: $record->id,
                context: $stats
            );

            return $record->fresh();
        });
    }

    /**
     * Обновление месячной статистики.
     */
    public function updateMonthlyStats(Staff $record, array $monthlyStats): Staff
    {
        $correlationId = (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_update_monthly_stats',
            amount: 0,
            correlationId: $correlationId
        );

        $record->updateMonthlyStats($monthlyStats);

        $this->logAction(
            action: 'monthly_stats_updated',
            entityType: 'Staff',
            entityId: $record->id,
            context: [
                'monthly_stats' => $monthlyStats,
                'tenant_id' => $record->tenant_id,
            ]
        );

        return $record->fresh();
    }

    /**
     * Получение списка сотрудников с фильтрами.
     */
    public function list(array $filters = []): Collection
    {
        $query = Staff::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (!empty($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        return $query
            ->with(['user', 'manager'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получение топ performers по выручке.
     */
    public function getTopPerformers(int $limit = 10): Collection
    {
        return Staff::active()
            ->topPerformers($limit)
            ->with(['user', 'manager'])
            ->get();
    }

    /**
     * Получение сотрудника по ID.
     */
    public function getById(int $id): Staff
    {
        return Staff::with(['user', 'manager', 'subordinates'])->findOrFail($id);
    }

    /**
     * Получение сотрудника по UUID.
     */
    public function getByUuid(string $uuid): Staff
    {
        return Staff::with(['user', 'manager', 'subordinates'])->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Получение статистики по всем сотрудникам тенанта.
     */
    public function getTenantStats(): array
    {
        $tenantId = tenant()?->id;
        if (!$tenantId) {
            return [];
        }

        $allStaff = Staff::where('tenant_id', $tenantId)->get();

        return [
            'total' => $allStaff->count(),
            'active' => $allStaff->where('status', 'active')->count(),
            'inactive' => $allStaff->where('status', 'inactive')->count(),
            'on_vacation' => $allStaff->where('status', 'on_vacation')->count(),
            'archived' => $allStaff->where('status', 'archived')->count(),
            'total_revenue' => $allStaff->sum('total_revenue_in_rubles'),
            'total_orders' => $allStaff->sum('total_orders_processed'),
            'avg_satisfaction' => $allStaff->avg('customer_satisfaction_score') ?? 0,
            'avg_efficiency' => $allStaff->map(fn($s) => $s->calculateEfficiencyScore())->avg() ?? 0,
            'by_role' => $allStaff->groupBy('role')->map(fn($group) => $group->count())->toArray(),
            'by_department' => $allStaff->groupBy('department')->map(fn($group) => $group->count())->toArray(),
        ];
    }

    /**
     * Изменение роли сотрудника.
     */
    public function changeRole(Staff $record, string $newRole): Staff
    {
        $correlationId = (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_role_change',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($record, $newRole, $correlationId): Staff {
            $oldRole = $record->role;
            $record->update(['role' => $newRole]);

            $this->logger->info('Staff role changed', [
                'uuid' => $record->uuid,
                'old_role' => $oldRole,
                'new_role' => $newRole,
                'correlation_id' => $correlationId,
            ]);

            $this->logAction(
                action: 'role_changed',
                entityType: 'Staff',
                entityId: $record->id,
                context: [
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                    'full_name' => $record->full_name,
                ]
            );

            return $record->fresh();
        });
    }

    /**
     * Изменение статуса сотрудника.
     */
    public function changeStatus(Staff $record, string $newStatus): Staff
    {
        $correlationId = (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_change_status',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($record, $newStatus, $correlationId): Staff {
            $oldStatus = $record->status;
            $record->update(['status' => $newStatus]);

            $this->logger->info('Staff status changed', [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'correlation_id' => $correlationId,
            ]);

            $this->logAction(
                action: 'status_changed',
                entityType: 'Staff',
                entityId: $record->id,
                context: [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'full_name' => $record->full_name,
                ]
            );

            return $record->fresh();
        });
    }
}
