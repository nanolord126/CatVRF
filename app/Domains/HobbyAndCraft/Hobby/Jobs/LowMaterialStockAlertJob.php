<?php declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final class LowMaterialStockAlertJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public int $tries = 3;
        public int $backoff = 60;

        /**
         * Create a new job instance.
         */
        public function __construct(public ?string $correlationId = null,
        private readonly Request $request, private readonly LoggerInterface $logger) {}

        /**
         * Handle the stock Audit.
         */
        public function handle(): void
        {
            $cid = $this->correlationId ?? (string) Str::uuid();

            $this->logger->info('Low Stock Audit Started (Hobby)', [
                'cid' => $cid,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            try {
                // 1. Fetch products with critical stock (< 5 units)
                $criticalMaterials = HobbyProduct::where('is_active', true)
                    ->where('stock_quantity', '<', 5)
                    ->with(['store'])
                    ->get();

                if ($criticalMaterials->isEmpty()) {
                    $this->logger->info('Hobby Inventory Check: All materials in stock.', [
                        'cid' => $cid,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                    return;
                }

                // 2. Group by Store for Alert Escalation
                $groupedByStore = $criticalMaterials->groupBy('store_id');

                foreach ($groupedByStore as $storeId => $materials) {
                    $store = $materials->first()->store;

                    $this->logger->warning('Critical Stock Alert for Hobby Store', [
                        'store' => $store->name,
                        'tenant_id' => $store->tenant_id,
                        'count' => $materials->count(),
                        'cid' => $cid,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                    // 3. Automated Notification (Surrogate for Notification Service)
                    $this->notifyStoreOwner($store, $materials);

                    // 4. Update model metadata via audit tags to mark as "alerted"
                    foreach ($materials as $material) {
                        $material->update([
                            'tags' => array_unique(array_merge($material->tags ?? [], ['low_stock_notified']))
                        ]);
                    }
                }

                $this->logger->info('Low Stock Audit Completed (Hobby)', [
                    'processed_stores' => $groupedByStore->count(),
                    'cid' => $cid,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            } catch (\Throwable $e) {
                $this->logger->error('Low Stock Job Failure', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'cid' => $cid,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                throw $e;
            }
        }

        /**
         * Notification Dispatch Surrogate.
         */
        private function notifyStoreOwner($store, $materials): void
        {
            // Actual logic: dispatch(new SendHobbyStockNotification($store, $materials))
            $skus = $materials->pluck('sku')->toArray();

            $this->logger->info("Email notification queued for Store: {$store->name} (SKUs: " . implode(',', $skus) . ")");
        }
}

