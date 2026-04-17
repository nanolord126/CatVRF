<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use App\Domains\Fashion\Services\FashionReviewModerationService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class BatchModerateReviewsJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        private readonly array $reviewIds,
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('moderation');
    }

    public function handle(FashionReviewModerationService $service): void
    {
        try {
            $service->batchModerateReviews($this->reviewIds, $this->correlationId);
            
            Log::channel('audit')->info('Reviews batch moderated', [
                'tenant_id' => $this->tenantId,
                'review_count' => count($this->reviewIds),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to batch moderate reviews', [
                'tenant_id' => $this->tenantId,
                'review_count' => count($this->reviewIds),
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
