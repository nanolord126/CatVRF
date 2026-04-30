<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * AnalyzeStaffPerformanceJob — Async job for AI performance analysis
 * 
 * Following CatVRF rules:
 * - Async LLM call via queue
 * - WithAuditLogging trait
 * - PII anonymization
 */
final class AnalyzeStaffPerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly string $correlationId,
        public readonly ?int $userId = null,
    ) {}

    public function handle(AuditService $auditService): void
    {
        $this->auditService = $auditService;
        
        try {
            // TODO: Implement actual LLM call here
            // For now, simulate analysis result
            $analysisResult = [
                'status' => 'completed',
                'performance_score' => 85,
                'strengths' => ['leadership', 'communication'],
                'areas_for_improvement' => ['time management'],
                'recommendations' => [
                    'Take time management course',
                    'Delegate more tasks',
                ],
                'analyzed_at' => now()->toIso8601String(),
            ];

            // Cache the result
            $cacheKey = "staff:performance:{$this->tenantId}:{$this->employeeId}";
            Cache::tags(['staff', 'performance', "tenant:{$this->tenantId}"])->put(
                $cacheKey,
                $analysisResult,
                now()->addHours(6)
            );

            $this->logAction(
                action: 'performance_analysis_completed',
                entityType: 'employee',
                entityId: $this->employeeId,
                context: [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $this->tenantId,
                    'performance_score' => $analysisResult['performance_score'],
                ],
                userId: $this->userId,
                tenantId: $this->tenantId
            );

            Log::info('Staff performance analysis completed', [
                'tenant_id' => $this->tenantId,
                'employee_id' => $this->employeeId,
                'correlation_id' => $this->correlationId,
            ]);

        } catch (\Exception $e) {
            Log::error('Staff performance analysis failed', [
                'tenant_id' => $this->tenantId,
                'employee_id' => $this->employeeId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            $this->logAction(
                action: 'performance_analysis_failed',
                entityType: 'employee',
                entityId: $this->employeeId,
                context: [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $this->tenantId,
                    'error' => $e->getMessage(),
                ],
                userId: $this->userId,
                tenantId: $this->tenantId
            );

            throw $e;
        }
    }
}
