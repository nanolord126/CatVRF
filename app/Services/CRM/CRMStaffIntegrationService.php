<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Services\StaffRoleService;
use App\Services\FraudControlService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * CRMStaffIntegrationService — интеграция CRM с RBAC для сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Управляет доступом к данным сотрудников в CRM на основе ролей.
 */
final class CRMStaffIntegrationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly StaffRoleService $roleService,
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Возвращает сотрудников, доступных для просмотра в CRM по роли.
     */
    public function getAccessibleStaff(?int $tenantId = null): array
    {
        $currentStaff = $this->getCurrentStaff();
        
        if (!$currentStaff) {
            return [];
        }

        $currentRole = StaffRole::tryFrom($currentStaff->role);
        
        return match ($currentRole) {
            StaffRole::EMPLOYEE => [
                'data' => [$currentStaff->toArray()],
                'scope' => 'own',
                'permissions' => ['staff.view_own'],
            ],
            StaffRole::OBJECT_ADMIN => [
                'data' => Staff::where('tenant_id', $currentStaff->tenant_id)
                    ->get()
                    ->toArray(),
                'scope' => 'object',
                'permissions' => ['staff.view_object_staff'],
            ],
            StaffRole::ACCOUNTANT, StaffRole::ANALYST => [
                'data' => Staff::where('tenant_id', $currentStaff->tenant_id)
                    ->get()
                    ->toArray(),
                'scope' => 'tenant',
                'permissions' => ['staff.view_all'],
            ],
            StaffRole::SMM_MANAGER, StaffRole::MANAGER => [
                'data' => Staff::where('tenant_id', $currentStaff->tenant_id)
                    ->get()
                    ->toArray(),
                'scope' => 'tenant',
                'permissions' => ['staff.view_all', 'staff.manage_all'],
            ],
            StaffRole::BUSINESS_OWNER => [
                'data' => Staff::where('tenant_id', $currentStaff->tenant_id)
                    ->get()
                    ->toArray(),
                'scope' => 'tenant',
                'permissions' => ['staff.view_all', 'staff.manage_all', 'staff.finance.manage_all'],
            ],
            StaffRole::INVESTOR => [
                'data' => Staff::where('tenant_id', $currentStaff->tenant_id)
                    ->get()
                    ->toArray(),
                'scope' => 'tenant',
                'permissions' => ['staff.view_all', 'staff.analytics.view_all'],
            ],
            default => [],
        };
    }

    /**
     * Возвращает финансовые данные, доступные для роли в CRM.
     */
    public function getAccessibleFinancialData(?int $tenantId = null): array
    {
        $currentStaff = $this->getCurrentStaff();
        
        if (!$currentStaff) {
            return [];
        }

        $currentRole = StaffRole::tryFrom($currentStaff->role);
        
        $cacheKey = "crm_finance_data:{$currentStaff->tenant_id}:{$currentRole->value}";

        return Cache::tags(['crm_staff', 'staff_finance'])->remember(
            $cacheKey,
            now()->addHours(2),
            function () use ($currentStaff, $currentRole) {
                $accessibleFields = match ($currentRole) {
                    StaffRole::EMPLOYEE => [],
                    StaffRole::OBJECT_ADMIN => [],
                    StaffRole::ACCOUNTANT => [
                        'salaries',
                        'expenses',
                        'revenue',
                        'payments',
                    ],
                    StaffRole::ANALYST => [
                        'revenue',
                        'expenses',
                        'performance_metrics',
                    ],
                    StaffRole::SMM_MANAGER => [],
                    StaffRole::MANAGER => [
                        'revenue',
                        'performance_metrics',
                        'budget',
                    ],
                    StaffRole::BUSINESS_OWNER => [
                        'salaries',
                        'expenses',
                        'revenue',
                        'payments',
                        'budget',
                        'roi',
                        'profit',
                    ],
                    StaffRole::INVESTOR => [
                        'revenue',
                        'expenses',
                        'roi',
                        'profit',
                    ],
                    default => [],
                };

                return [
                    'accessible_fields' => $accessibleFields,
                    'tenant_id' => $currentStaff->tenant_id,
                    'role' => $currentRole->value,
                ];
            },
        );
    }

    /**
     * Возвращает аналитические данные, доступные для роли в CRM.
     */
    public function getAccessibleAnalytics(?int $tenantId = null): array
    {
        $currentStaff = $this->getCurrentStaff();
        
        if (!$currentStaff) {
            return [];
        }

        $currentRole = StaffRole::tryFrom($currentStaff->role);
        
        return match ($currentRole) {
            StaffRole::EMPLOYEE => [
                'own_performance' => true,
                'own_wellness' => true,
                'own_learning' => true,
                'team_analytics' => false,
                'financial_analytics' => false,
            ],
            StaffRole::OBJECT_ADMIN => [
                'own_performance' => true,
                'own_wellness' => true,
                'own_learning' => true,
                'team_analytics' => true,
                'object_analytics' => true,
                'financial_analytics' => false,
            ],
            StaffRole::ACCOUNTANT => [
                'own_performance' => true,
                'financial_analytics' => true,
                'team_analytics' => false,
                'performance_analytics' => false,
            ],
            StaffRole::ANALYST => [
                'all_analytics' => true,
                'performance_analytics' => true,
                'wellness_analytics' => true,
                'learning_analytics' => true,
                'financial_analytics' => true,
                'forecasts' => true,
            ],
            StaffRole::SMM_MANAGER => [
                'social_analytics' => true,
                'communication_analytics' => true,
                'gamification_analytics' => true,
                'team_analytics' => true,
            ],
            StaffRole::MANAGER => [
                'all_analytics' => true,
                'team_analytics' => true,
                'performance_analytics' => true,
                'schedule_analytics' => true,
                'financial_analytics' => false,
            ],
            StaffRole::BUSINESS_OWNER => [
                'all_analytics' => true,
                'financial_analytics' => true,
                'forecasts' => true,
            ],
            StaffRole::INVESTOR => [
                'all_analytics' => true,
                'financial_analytics' => true,
                'performance_analytics' => true,
                'forecasts' => true,
            ],
            default => [],
        };
    }

    /**
     * Проверяет доступ к CRM-функциям по роли.
     */
    public function canAccessCRMFeature(string $feature): bool
    {
        $currentStaff = $this->getCurrentStaff();
        
        if (!$currentStaff) {
            return false;
        }

        $currentRole = StaffRole::tryFrom($currentStaff->role);
        
        if (!$currentRole) {
            return false;
        }

        $featurePermissions = [
            'staff_management' => ['staff.create', 'staff.update', 'staff.delete'],
            'schedule_management' => ['staff.manage_schedule', 'staff.approve_timeoff'],
            'financial_management' => ['staff.finance.manage_all'],
            'analytics_dashboard' => ['staff.analytics.view_all'],
            'social_features' => ['staff.social.manage_posts'],
            'gamification' => ['staff.gamification.manage_all'],
            'learning_management' => ['staff.learning.manage_all'],
            'communication' => ['staff.communication.manage_all'],
        ];

        $requiredPermissions = $featurePermissions[$feature] ?? [];
        
        foreach ($requiredPermissions as $permission) {
            if ($currentRole->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Возвращает права на экспорт данных по роли.
     */
    public function getExportPermissions(): array
    {
        $currentStaff = $this->getCurrentStaff();
        
        if (!$currentStaff) {
            return [];
        }

        $currentRole = StaffRole::tryFrom($currentStaff->role);
        
        return match ($currentRole) {
            StaffRole::EMPLOYEE => [],
            StaffRole::OBJECT_ADMIN => ['schedule', 'attendance'],
            StaffRole::ACCOUNTANT => ['financial', 'salaries', 'expenses'],
            StaffRole::ANALYST => ['analytics', 'performance', 'forecasts'],
            StaffRole::SMM_MANAGER => ['social', 'communication'],
            StaffRole::MANAGER => ['staff', 'schedule', 'performance'],
            StaffRole::BUSINESS_OWNER => ['all'],
            StaffRole::INVESTOR => ['financial', 'analytics'],
            default => [],
        };
    }

    /**
     * Возвращает текущего сотрудника.
     */
    private function getCurrentStaff(): ?Staff
    {
        $userId = Auth::id();
        
        if (!$userId) {
            return null;
        }

        return Staff::where('user_id', $userId)->first();
    }
}
