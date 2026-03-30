<?php declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowMaterialStockAlertJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $backoff = 60;

        /**
         * Create a new job instance.
         */
        public function __construct(
            public readonly ?string $correlationId = null
        ) {}

        /**
         * Handle the stock Audit.
         */
        public function handle(): void
        {
            $cid = $this->correlationId ?? (string) Str::uuid();

            Log::channel('audit')->info('Low Stock Audit Started (Hobby)', [
                'cid' => $cid
            ]);

            try {
                // 1. Fetch products with critical stock (< 5 units)
                $criticalMaterials = HobbyProduct::where('is_active', true)
                    ->where('stock_quantity', '<', 5)
                    ->with(['store'])
                    ->get();

                if ($criticalMaterials->isEmpty()) {
                    Log::channel('audit')->info('Hobby Inventory Check: All materials in stock.', [
                        'cid' => $cid
                    ]);
                    return;
                }

                // 2. Group by Store for Alert Escalation
                $groupedByStore = $criticalMaterials->groupBy('store_id');

                foreach ($groupedByStore as $storeId => $materials) {
                    $store = $materials->first()->store;

                    Log::channel('audit')->warning('Critical Stock Alert for Hobby Store', [
                        'store' => $store->name,
                        'tenant_id' => $store->tenant_id,
                        'count' => $materials->count(),
                        'cid' => $cid
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

                Log::channel('audit')->info('Low Stock Audit Completed (Hobby)', [
                    'processed_stores' => $groupedByStore->count(),
                    'cid' => $cid
                ]);

            } catch (\Throwable $e) {
                Log::channel('audit')->error('Low Stock Job Failure', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'cid' => $cid
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

            Log::info("Email notification queued for Store: {$store->name} (SKUs: " . implode(',', $skus) . ")");
        }
}
