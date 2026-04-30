<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Analytics\Application\UseCases\CalculateRFMScoreUseCase;
use Modules\Analytics\Domain\Events\RFMScoreCalculated;

/**
 * Calculate RFM Score Job
 *
 * Async job for RFM (Recency, Frequency, Monetary) score calculation.
 * Runs asynchronously to avoid blocking main request flow.
 * Follows production pattern: no LLM in transactions, async processing.
 */
final class CalculateRFMScoreJob implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;
    use WithAuditLogging;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $userId,
        public readonly ?int $tenantId = null,
    ) {}

    public function handle(
        CalculateRFMScoreUseCase $calculateRFMUseCase,
        AuditService $auditService,
    ): void {
        try {
            $result = $calculateRFMUseCase->execute($this->userId);

            $this->logAction(
                action: 'rfm_score_calculated_async',
                entityType: 'User',
                entityId: $this->userId,
                context: [
                    'tenant_id' => $this->tenantId,
                    'recency_score' => $result['recency_score'] ?? null,
                    'frequency_score' => $result['frequency_score'] ?? null,
                    'monetary_score' => $result['monetary_score'] ?? null,
                    'overall_score' => $result['overall_score'] ?? null,
                ],
                userId: $this->userId,
                tenantId: $this->tenantId
            );
        } catch (\Exception $e) {
            $this->logError(
                operation: 'rfm_score_calculation',
                exception: $e,
                context: [
                    'user_id' => $this->userId,
                    'tenant_id' => $this->tenantId,
                ]
            );

            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logError(
            operation: 'rfm_score_job_failed',
            exception: $exception instanceof \Exception ? $exception : new \Exception($exception->getMessage()),
            context: [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
            ]
        );
    }
}
