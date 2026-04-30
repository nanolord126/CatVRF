<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Jobs;

use LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Services\ElectronicsService;
use App\Services\AI\DemandForecastService;
use Illuminate\Support\Str;
use DateTime;
use Exception;

final class ElectronicsInventoryAuditJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly int $tenantId,
        private readonly string $correlationId) {}

    /**
     * Execute the job.
     */
    public function handle(
        ElectronicsService $electronicsService,
        DemandForecastService $demandForecast,
        LoggerInterface $logger
    ): void {
        $correlationId = $this->correlationId !== '' ? $this->correlationId : Str::uuid()->toString();

        $logger->$this->logger->info('LAYER-8: Electronics Inventory Audit JOB START', [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $correlationId,
        ]);

        try {
            // 1. Fetch low stock products for this electronics domain
            $lowStockItems = ElectronicsProduct::where('availability_status', 'low_stock')
                ->orWhere('availability_status', 'out_of_stock')
                ->get();

            $logger->$this->logger->info('LAYER-8: Found gadgets for reorder check', [
                'count' => iterator_iterator_count($lowStockItems),
                'correlation_id' => $correlationId,
            ]);

            // 2. Predict demand for each item using ML Domain Service
            foreach ($lowStockItems as $product) {
                $now = new DateTime();
                $endDate = (clone $now)->modify('+30 days');
                $forecast = $demandForecast->forecastForItem(
                    $product->id,
                    $now,
                    $endDate
                );

                if ($forecast['predicted_demand'] > 50) {
                    $logger->warning('LAYER-8: HIGH DEMAND GADGET ALERT', [
                        'sku' => $product->sku,
                        'forecast' => $forecast['predicted_demand'],
                        'correlation_id' => $correlationId,
                    ]);

                    // Trigger restocking logic if necessary
                    $electronicsService->adjustStock($product->id, 10, 'Automated reorder based on forecast', $correlationId);
                }
            }

            $logger->$this->logger->info('LAYER-8: Electronics Inventory Audit JOB COMPLETE', [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $correlationId,
            ]);

        } catch (Exception $e) {
            $logger->error('LAYER-8: Electronics Inventory Audit JOB FAILED', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    
    public function failed(\Throwable $exception): void
    {
        $this->loggerInterface->error('electronics job failed', [
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
