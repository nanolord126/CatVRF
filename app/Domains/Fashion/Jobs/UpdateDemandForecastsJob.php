<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use App\Domains\Fashion\Services\FashionInventoryForecastingService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class UpdateDemandForecastsJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('forecasting');
        $this->delay(now()->addHours(6));
    }

    public function handle(FashionInventoryForecastingService $service): void
    {
        try {
            $productIds = DB::table('fashion_products')
                ->where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->pluck('id')
                ->toArray();

            foreach ($productIds as $productId) {
                $service->forecastDemand($productId, 30, $this->correlationId);
            }
            
            Log::channel('audit')->info('Demand forecasts updated', [
                'tenant_id' => $this->tenantId,
                'product_count' => count($productIds),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update demand forecasts', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
