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
 * GenerateTrainingRecommendationsJob — Async job for training recommendations
 */
final class GenerateTrainingRecommendationsJob implements ShouldQueue
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
            // TODO: Implement actual LLM call for training recommendations
            $recommendations = [
                'status' => 'completed',
                'recommended_courses' => [
                    ['course_id' => 1, 'priority' => 'high', 'reason' => 'Skill gap'],
                    ['course_id' => 2, 'priority' => 'medium', 'reason' => 'Career growth'],
                ],
                'generated_at' => now()->toIso8601String(),
            ];

            $cacheKey = "staff:training_recommendations:{$this->tenantId}:{$this->employeeId}";
            Cache::tags(['staff', 'training', "tenant:{$this->tenantId}"])->put(
                $cacheKey,
                $recommendations,
                now()->addDays(7)
            );

            $this->logAction(
                action: 'training_recommendations_completed',
                entityType: 'employee',
                entityId: $this->employeeId,
                context: [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $this->tenantId,
                ],
                userId: $this->userId,
                tenantId: $this->tenantId
            );

        } catch (\Exception $e) {
            Log::error('Training recommendations generation failed', [
                'tenant_id' => $this->tenantId,
                'employee_id' => $this->employeeId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
