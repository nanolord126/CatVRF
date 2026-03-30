<?php declare(strict_types=1);

namespace App\Services\Stationery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StationeryService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraud,
            private string $correlationId = ''
        ) {
            // Ensure correlation_id is present for tracing
            $this->correlationId = $correlationId ?: (string) Str::uuid();
        }

        /**
         * Creates a new stationery product with mandatory transaction and audit.
         *
         * @param array $data Validated product data
         * @return StationeryProduct
         * @throws \Throwable
         */
        public function createProduct(array $data): StationeryProduct
        {
            Log::channel('audit')->info('Attempting to create stationery product', [
                'sku' => $data['sku'] ?? 'unknown',
                'correlation_id' => $this->correlationId,
            ]);

            // Pre-mutation fraud check
            $this->fraud->check([
                'operation' => 'product_create',
                'tenant_id' => $data['tenant_id'] ?? tenant()->id,
                'correlation_id' => $this->correlationId,
            ]);

            return DB::transaction(function () use ($data) {
                $product = StationeryProduct::create(array_merge($data, [
                    'correlation_id' => $this->correlationId,
                ]));

                Log::channel('audit')->info('Stationery product created successfully', [
                    'uuid' => $product->uuid,
                    'correlation_id' => $this->correlationId,
                ]);

                return $product;
            });
        }

        /**
         * Updates stock level via InventoryManagementService logic (simulated here for isolation).
         *
         * @param int $productId
         * @param int $adjustment Positive or negative
         * @param string $reason
         * @return bool
         */
        public function adjustStock(int $productId, int $adjustment, string $reason): bool
        {
            return DB::transaction(function () use ($productId, $adjustment, $reason) {
                $product = StationeryProduct::lockForUpdate()->findOrFail($productId);

                $newQuantity = $product->stock_quantity + $adjustment;

                if ($newQuantity < 0) {
                    Log::channel('audit')->warning('Negative stock prevention triggered', [
                        'product_id' => $productId,
                        'adjustment' => $adjustment,
                        'correlation_id' => $this->correlationId,
                    ]);
                    throw new \DomainException('Insufficient stock quality in stationery warehouse.');
                }

                $product->update([
                    'stock_quantity' => $newQuantity,
                    'correlation_id' => $this->correlationId,
                ]);

                Log::channel('audit')->info('Stock adjusted for stationery product', [
                    'product_id' => $productId,
                    'adjustment' => $adjustment,
                    'reason' => $reason,
                    'new_stock' => $newQuantity,
                    'correlation_id' => $this->correlationId,
                ]);

                return true;
            });
        }

        /**
         * Retrieves pricing for a consumer.
         * B2B logic applies if business_group_id is active.
         */
        public function resolvePrice(int $productId, ?int $businessGroupId = null): int
        {
            $product = StationeryProduct::findOrFail($productId);

            if ($businessGroupId !== null) {
                // Check for B2B contract or specific business group rules
                return $product->b2b_price_cents ?? $product->price_cents;
            }

            return $product->price_cents;
        }

        /**
         * Fetches stores with low stock for reactive business management.
         */
        public function getLowStockProducts(int $tenantId): \Illuminate\Support\Collection
        {
            return StationeryProduct::where('tenant_id', $tenantId)
                ->whereRaw('stock_quantity <= min_stock_threshold')
                ->where('is_active', true)
                ->with('store')
                ->get();
        }
}
