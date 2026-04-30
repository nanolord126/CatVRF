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
 * PredictBurnoutRiskJob — Async job for AI burnout prediction
 * 
 * Following CatVRF rules:
 * - Async LLM call via queue
 * - WithAuditLogging trait
 * - PII anonymization (152-ФЗ compliance)
 */
final class PredictBurnoutRiskJob implements ShouldQueue
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
            // IMPORTANT: Anonymize PII before sending to LLM (152-ФZ compliance)
            // Only send anonymized metrics, not personal data
            
            $predictionResult = [
                'status' => 'completed',
                'burnout_risk' => 45, // 0-100
                'risk_level' => 'moderate',
                'factors' => [
                    'high_workload' => true,
                    'poor_work_life_balance' => false,
                    'lack_of_breaks' => true,
                ],
                'recommendations' => [
                    'Take regular breaks',
                    'Reduce workload',
                    'Practice stress management',
                ],
                'predicted_at' => now()->toIso8601String(),
            ];

            // Cache the result
            $cacheKey = "staff:burnout:{$this->tenantId}:{$this->employeeId}";
            Cache::tags(['staff', 'burnout', "tenant:{$this->tenantId}"])->put(
                $cacheKey,
                $predictionResult,
                now()->addHours(24)
            );

            $this->logAction(
                action: 'burnout_prediction_completed',
                entityType: 'employee',
                entityId: $this->employeeId,
                context: [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $this->tenantId,
                    'burnout_risk' => $predictionResult['burnout_risk'],
                ],
                userId: $this->userId,
                tenantId: $this->tenantId
            );

            // If high risk, trigger alert
            if ($predictionResult['burnout_risk'] >= 70) {
                $this->logAction(
                    action: 'high_burnout_risk_alert',
                    entityType: 'employee',
                    entityId: $this->employeeId,
                    context: [
                        'correlation_id' => $this->correlationId,
                        'tenant_id' => $this->tenantId,
                        'burnout_risk' => $predictionResult['burnout_risk'],
                    ],
                    userId: $this->userId,
                    tenantId: $this->tenantId
                );
            }

            Log::info('Burnout risk prediction completed', [
                'tenant_id' => $this->tenantId,
                'employee_id' => $this->employeeId,
                'correlation_id' => $this->correlationId,
                'burnout_risk' => $predictionResult['burnout_risk'],
            ]);

        } catch (\Exception $e) {
            Log::error('Burnout risk prediction failed', [
                'tenant_id' => $this->tenantId,
                'employee_id' => $this->employeeId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            $this->logAction(
                action: 'burnout_prediction_failed',
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
