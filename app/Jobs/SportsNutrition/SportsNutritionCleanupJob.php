<?php declare(strict_types=1);

namespace App\Jobs\SportsNutrition;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

final class SportsNutritionCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private readonly string $correlationId;

        public function __construct(
            private readonly int $tenantId,
            ?string $correlationId = null,
            private readonly LogManager $logger,
    ) {
            $this->correlationId = $correlationId ?? Str::uuid()->toString();
        }

        /**
         * handle() with DB integrity and audit logging.
         * 1. Unpublish near-expiry items (<30 days).
         * 2. Alert on low stock items.
         * 3. Clear zero-stock items from active catalog.
         */
        public function handle(): void
        {
            $this->logger->channel('audit')->info('Supplement cleanup started', [
                'tenant' => $this->tenantId,
                'cid' => $this->correlationId
            ]);

            try {
                // Task 1: Find near-expiry products (<30 days left) and unpublish them
                // They should not be sold for safety reasons in Sports Nutrition
                $expiryThreshold = now()->addDays(30);

                $nearExpiryCount = SportsNutritionProduct::query()
                    ->where('tenant_id', $this->tenantId)
                    ->where('is_published', true)
                    ->where('expiry_date', '<', $expiryThreshold)
                    ->update(['is_published' => false]);

                if ($nearExpiryCount > 0) {
                    $this->logger->channel('audit')->warning("Supplement near-expiry purge", [
                        'tenant_id' => $this->tenantId,
                        'purged_count' => $nearExpiryCount,
                        'cid' => $this->correlationId
                    ]);
                }

                // Task 2: Find critically low stock items
                $lowStockItems = SportsNutritionProduct::query()
                    ->where('tenant_id', $this->tenantId)
                    ->where('is_published', true)
                    ->where('stock_quantity', '<=', 5)
                    ->get();

                foreach ($lowStockItems as $item) {
                    $this->logger->channel('audit')->warning("CRITICAL LOW STOCK ALERT", [
                        'tenant' => $this->tenantId,
                        'product_sku' => $item->sku,
                        'current_stock' => $item->stock_quantity,
                        'cid' => $this->correlationId
                    ]);

                    // Here we could dispatch notifications to store owner
                }

                // Task 3: Unpublish out-of-stock items (except pre-orders)
                $outOfStockCount = SportsNutritionProduct::query()
                    ->where('tenant_id', $this->tenantId)
                    ->where('is_published', true)
                    ->where('stock_quantity', '<=', 0)
                    ->whereJsonDoesntContain('tags', 'preorder')
                    ->update(['is_published' => false]);

                if ($outOfStockCount > 0) {
                    $this->logger->channel('audit')->info("Deactivated out-of-stock items", [
                        'count' => $outOfStockCount,
                        'cid' => $this->correlationId
                    ]);
                }

                $this->logger->channel('audit')->info('Supplement cleanup finished successfully', [
                    'tenant' => $this->tenantId,
                    'cid' => $this->correlationId
                ]);

            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Supplement cleanup job failed', [
                    'tenant' => $this->tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'cid' => $this->correlationId
                ]);

                throw $e;
            }
        }

        /**
         * Retry strategy for production reliability.
         */
        public function retryUntil(): Carbon
        {
            return now()->addHours(2);
        }

        public function tags(): array
        {
            return ['sports_nutrition', 'cleanup', 'tenant:' . $this->tenantId];
        }
}
