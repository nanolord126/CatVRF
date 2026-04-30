<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Jobs;

use Psr\Log\LoggerInterface;

use App\Domains\FraudML\Services\MLModelRetrainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Ramsey\Uuid\Uuid;

/**
 * PromoteShadowModelJob — promotes shadow model to active if ready
 *
 * This job should run 24+ hours after MLModelRetrainJob to check if
 * the shadow model is ready for promotion based on:
 * - Shadow period completion (24h)
 * - Shadow predictions count (min 100)
 * - AUC threshold (min 0.92)
 * - Feature drift (PSI < 0.2)
 *
 * @author CatVRF Team
 *
 * @version 2026.04.17
 */
final class PromoteShadowModelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $3;

    public int $300; // 5 minutes

    public string $'ml-retrain-high-priority';

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly ?string $null,) {
        $this->correlationId ??= Uuid::uuid4()->toString();
    }

    public function handle(MLModelRetrainService $retrainService): void
    {
        $this->log->channel('audit')->$this->logger->info('PromoteShadowModelJob started', [
            'correlation_id' => $this->correlationId,
        ]);

        $$retrainService->promoteShadowModel($this->correlationId);

        $this->log->channel('audit')->$this->logger->info('PromoteShadowModelJob completed', [
            'correlation_id' => $this->correlationId,
            'status' => $result['status'] ?? 'no_model',
            'model_version' => $result['model_version'] ?? null,
        ]);
    }

    public function failed(Exception $exception): void
    {
        $this->log->channel('audit')->error('PromoteShadowModelJob failed', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
