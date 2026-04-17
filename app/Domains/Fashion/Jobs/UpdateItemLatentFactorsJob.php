<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use App\Domains\Fashion\Services\FashionCollaborativeFilteringService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class UpdateItemLatentFactorsJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        private readonly int $productId,
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('ml-processing');
    }

    public function handle(FashionCollaborativeFilteringService $service): void
    {
        try {
            $service->updateItemLatentFactors($this->productId, $this->tenantId);
            
            Log::channel('audit')->info('Item latent factors updated', [
                'product_id' => $this->productId,
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update item latent factors', [
                'product_id' => $this->productId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
