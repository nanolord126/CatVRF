<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

/**
 * StaffPerformanceAnalyzerService — AI-анализ производительности сотрудников
 * 
 * Following CatVRF rules:
 * - Async LLM calls via queue
 * - WithAuditLogging trait
 * - Cache with tags
 * - PII anonymization
 */
final class StaffPerformanceAnalyzerService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Анализировать производительность сотрудника
     * LLM вызов асинхронно через Job
     */
    public function analyzePerformance(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();
        
        $cacheKey = "staff:performance:{$tenantId}:{$employeeId}";
        
        $result = Cache::tags(['staff', 'performance', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $employeeId, $correlationId, $userId) {
                // Диспатчим Job для асинхронного LLM анализа
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\AnalyzeStaffPerformanceJob(
                    tenantId: $tenantId,
                    employeeId: $employeeId,
                    correlationId: $correlationId,
                    userId: $userId,
                ));

                // Возвращаем промежуточный результат
                return [
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                    'message' => 'AI analysis in progress',
                ];
            }
        );

        $this->logAction(
            action: 'performance_analysis_requested',
            entityType: 'employee',
            entityId: $employeeId,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $result;
    }

    /**
     * Получить анализ производительности из кэша
     */
    public function getPerformanceAnalysis(int $tenantId, int $employeeId): ?array
    {
        $cacheKey = "staff:performance:{$tenantId}:{$employeeId}";
        
        return Cache::tags(['staff', 'performance', "tenant:{$tenantId}"])->get($cacheKey);
    }

    /**
     * Выявить паттерны производительности команды
     */
    public function identifyTeamPatterns(int $tenantId, int $departmentId, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();
        
        $cacheKey = "staff:patterns:{$tenantId}:{$departmentId}";
        
        $result = Cache::tags(['staff', 'patterns', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(12),
            function () use ($tenantId, $departmentId, $correlationId, $userId) {
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\AnalyzeTeamPatternsJob(
                    tenantId: $tenantId,
                    departmentId: $departmentId,
                    correlationId: $correlationId,
                    userId: $userId,
                ));

                return [
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                    'message' => 'Team pattern analysis in progress',
                ];
            }
        );

        $this->logAction(
            action: 'team_patterns_requested',
            entityType: 'department',
            entityId: $departmentId,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $result;
    }
}
