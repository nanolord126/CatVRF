<?php declare(strict_types=1);

namespace App\Domains\FraudML\Jobs;

use App\Domains\FraudML\Services\MLModelRetrainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
 * @version 2026.04.17
 */
final class PromoteShadowModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300; // 5 minutes
    
    public string $queue = 'ml-retrain-high-priority';

    public function __construct(
        private readonly ?string $correlationId = null,
    ) {
        $this->correlationId ??= Uuid::uuid4()->toString();
    }

    public function handle(MLModelRetrainService $retrainService): void
    {
        Log::channel('audit')->info('PromoteShadowModelJob started', [
            'correlation_id' => $this->correlationId,
        ]);

        $result = $retrainService->promoteShadowModel($this->correlationId);

        Log::channel('audit')->info('PromoteShadowModelJob completed', [
            'correlation_id' => $this->correlationId,
            'status' => $result['status'] ?? 'no_model',
            'model_version' => $result['model_version'] ?? null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('audit')->error('PromoteShadowModelJob failed', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
