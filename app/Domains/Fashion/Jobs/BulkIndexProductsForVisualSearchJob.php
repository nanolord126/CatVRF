<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use App\Domains\Fashion\Services\FashionVisualSearchService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class BulkIndexProductsForVisualSearchJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        private readonly array $productIds,
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('visual-search');
    }

    public function handle(FashionVisualSearchService $service): void
    {
        try {
            $service->bulkIndexProducts($this->productIds, $this->correlationId);
            
            Log::channel('audit')->info('Products indexed for visual search', [
                'tenant_id' => $this->tenantId,
                'product_count' => count($this->productIds),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to index products for visual search', [
                'tenant_id' => $this->tenantId,
                'product_count' => count($this->productIds),
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
