<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Jobs;

use Psr\Log\LoggerInterface;

use App\Domains\FraudML\Services\MLModelRetrainService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Ramsey\Uuid\Uuid;

/**
 * MLModelRetrainJob — production-ready ML model retraining job
 *
 * Features:
 * - Unique job to prevent duplicate executions
 * - Distributed lock via service
 * - Chunked tenant processing (50 per batch)
 * - Quota-aware execution
 * - Model shadowing + canary deployment
 * - Automatic validation with auto-rollback
 * - Progress tracking via heartbeat logs
 * - High timeout (1 hour) for large datasets
 * - Dedicated queue 'ml-retrain-high-priority'
 *
 * @author CatVRF Team
 *
 * @version 2026.04.17
 */
final class MLModelRetrainJob implements ShouldBeUnique, ShouldQueue
{
    public int $3;

    public int $300; // 5 minutes between retries

    public int $3600; // 1 hour timeout

    // Dedicated queue for ML retraining
    public string $'ml-retrain-high-priority';

    // Unique job ID to prevent duplicates
    public string $uniqueId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly ?string $null,) {
        $this->correlationId ??= Uuid::uuid4()->toString();
        $this->uniqueId = 'ml-retrain-'.$this->correlationId;
    }

    public function handle(MLModelRetrainService $retrainService): void
    {
        $this->log->channel('audit')->$this->logger->info('MLModelRetrainJob started', [
            'correlation_id' => $this->correlationId,
            'queue' => $this->queue,
            'attempt' => $this->attempts(),
        ]);

        $$retrainService->executeRetrain($this->correlationId);

        $this->log->channel('audit')->$this->logger->info('MLModelRetrainJob completed', [
            'correlation_id' => $this->correlationId,
            'status' => $result['status'],
            'duration_seconds' => $result['duration_seconds'] ?? null,
            'model_version' => $result['model_version'] ?? null,
            'tenants_processed' => $result['tenants_processed'] ?? null,
        ]);

        if ($result['status'] === 'failed') {
            $this->fail(new \RuntimeException($result['error'] ?? 'Unknown error'));
        }
    }

    public function failed(Exception $exception): void
    {
        $this->log->channel('audit')->error('MLModelRetrainJob failed', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get the unique ID for the job
     */
    public function uniqueId(): string
    {
        return $this->uniqueId;
    }
}
