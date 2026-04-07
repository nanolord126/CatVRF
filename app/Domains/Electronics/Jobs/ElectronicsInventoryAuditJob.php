<?php declare(strict_types=1);

namespace App\Domains\Electronics\Jobs;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class ElectronicsInventoryAuditJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $backoff = 60;

        /**
         * Create a new job instance.
         */
        public function __construct(
            private readonly int $tenantId,
            private string $correlationId = '', private readonly LoggerInterface $logger) {}

        /**
         * Execute the job.
         */
        public function handle(
            ElectronicsService $electronicsService,
            DemandForecastService $demandForecast,
        ): void {
            $correlationId = $this->correlationId ?: (string) Str::uuid();

            $this->logger->info('LAYER-8: Electronics Inventory Audit JOB START', [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $correlationId,
            ]);

            try {
                // 1. Fetch low stock products for this electronics domain
                $lowStockItems = ElectronicsProduct::where('availability_status', 'low_stock')
                    ->orWhere('availability_status', 'out_of_stock')
                    ->get();

                $this->logger->info('LAYER-8: Found gadgets for reorder check', [
                    'count' => $lowStockItems->count(),
                    'correlation_id' => $correlationId,
                ]);

                // 2. Predict demand for each item using ML Domain Service
                foreach ($lowStockItems as $product) {
                    $forecast = $demandForecast->forecastForItem(
                        $product->id,
                        Carbon::now(),
                        Carbon::now()->addDays(30)
                    );

                    if ($forecast['predicted_demand'] > 50) {
                        $this->logger->warning('LAYER-8: HIGH DEMAND GADGET ALERT', [
                            'sku' => $product->sku,
                            'forecast' => $forecast['predicted_demand'],
                            'correlation_id' => $correlationId,
                        ]);

                        // Trigger restocking logic if necessary
                        $electronicsService->adjustStock($product->id, 10, 'Automated reorder based on forecast', $correlationId);
                    }
                }

                $this->logger->info('LAYER-8: Electronics Inventory Audit JOB COMPLETE', [
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $correlationId,
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('LAYER-8: Electronics Inventory Audit JOB FAILED', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Define job tags for Horizon monitoring.
         */
        public function tags(): array
        {
            return ['electronics', 'inventory', 'tenant:' . $this->tenantId];
        }
}
