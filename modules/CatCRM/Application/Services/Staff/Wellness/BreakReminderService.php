<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Wellness;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;

/**
 * BreakReminderService — Сервис напоминаний о перерывах
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 */
final class BreakReminderService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Отправить напоминание о перерыве
     */
    public function sendBreakReminder(
        int $tenantId,
        int $employeeId,
        string $breakType, // 'short', 'lunch', 'rest'
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        $cacheKey = "staff:break_reminder:{$tenantId}:{$employeeId}:" . time();

        Cache::tags(['staff', 'breaks', "tenant:{$tenantId}"])->put(
            $cacheKey,
            [
                'employee_id' => $employeeId,
                'break_type' => $breakType,
                'sent_at' => now()->toIso8601String(),
            ],
            now()->addMinutes(30)
        );

        $this->logAction(
            action: 'break_reminder_sent',
            entityType: 'employee',
            entityId: $employeeId,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'break_type' => $breakType,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        // TODO: Dispatch notification (push, email, Slack)

        return [
            'reminder_sent' => true,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Настроить расписание напоминаний
     */
    public function setReminderSchedule(
        int $tenantId,
        int $employeeId,
        array $schedule, // [{'hour' => 10, 'minute' => 30, 'type' => 'short'}]
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        // TODO: Save schedule to database

        $this->logAction(
            action: 'break_reminder_schedule_set',
            entityType: 'employee',
            entityId: $employeeId,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'schedule' => $schedule,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return [
            'schedule_set' => true,
            'correlation_id' => $correlationId,
        ];
    }
}
