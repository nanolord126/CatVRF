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
 * MatchSkillsToTaskJob — Async job for skill matching
 */
final class MatchSkillsToTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly int $tenantId,
        public readonly array $requiredSkills,
        public readonly string $taskCategory,
        public readonly string $correlationId,
        public readonly ?int $userId = null,
    ) {}

    public function handle(AuditService $auditService): void
    {
        $this->auditService = $auditService;
        
        try {
            // TODO: Implement actual skill matching logic
            $matchingResult = [
                'status' => 'completed',
                'matched_employees' => [
                    ['employee_id' => 1, 'match_score' => 95],
                    ['employee_id' => 2, 'match_score' => 85],
                ],
                'matched_at' => now()->toIso8601String(),
            ];

            $cacheKey = "staff:skill_match:{$this->tenantId}:" . md5(json_encode($this->requiredSkills));
            Cache::tags(['staff', 'skill_matching', "tenant:{$this->tenantId}"])->put(
                $cacheKey,
                $matchingResult,
                now()->addHours(2)
            );

            $this->logAction(
                action: 'skill_matching_completed',
                entityType: 'task',
                entityId: null,
                context: [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $this->tenantId,
                    'matched_count' => count($matchingResult['matched_employees']),
                ],
                userId: $this->userId,
                tenantId: $this->tenantId
            );

        } catch (\Exception $e) {
            Log::error('Skill matching failed', [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
