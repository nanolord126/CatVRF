<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionInventoryManagementService
{
    private const CACHE_TTL = 1800;

    /**
     * Get inventory status for a product
     */
    public function getProductInventoryStatus(int $productId, int $tenantId): array
    {
        $cacheKey = "fashion_inventory:{$tenantId}:{$productId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($productId, $tenantId) {
            $product = DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$product) {
                return ['error' => 'Product not found'];
            }

            $status = $this->determineStockStatus($product);
            $restockDate = $this->getRestockDate($productId, $tenantId);

            return [
                'product_id' => $productId,
                'available_stock' => $product->available_stock,
                'reserved_stock' => $product->reserved_quantity ?? 0,
                'total_stock' => $product->available_stock + ($product->reserved_quantity ?? 0),
                'status' => $status,
                'low_stock_threshold' => $product->low_stock_threshold ?? 10,
                'restock_date' => $restockDate,
                'can_sell' => $product->available_stock > 0,
            ];
        });
    }

    /**
     * Determine stock status
     */
    private function determineStockStatus(object $product): string
    {
        $threshold = $product->low_stock_threshold ?? 10;

        if ($product->available_stock === 0) {
            return 'out_of_stock';
        }

        if ($product->available_stock <= $threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Get restock date for a product
     */
    private function getRestockDate(int $productId, int $tenantId): ?string
    {
        $restock = DB::table('fashion_inventory_forecasts')
            ->where('fashion_product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('restock_date', '>', Carbon::now())
            ->orderBy('restock_date')
            ->first();

        return $restock ? $restock->restock_date->toDateString() : null;
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStock(int $productId, int $quantity, int $orderId, int $tenantId): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $orderId, $tenantId) {
            $product = DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (!$product || $product->available_stock < $quantity) {
                Log::warning('Insufficient stock for reservation', [
                    'product_id' => $productId,
                    'requested' => $quantity,
                    'available' => $product->available_stock ?? 0,
                    'order_id' => $orderId,
                ]);
                return false;
            }

            // Reserve the stock
            DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->decrement('available_stock', $quantity);

            DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->increment('reserved_quantity', $quantity);

            // Record the reservation
            DB::table('fashion_item_reserves')->insert([
                'id' => uniqid(),
                'product_id' => $productId,
                'order_id' => $orderId,
                'quantity' => $quantity,
                'status' => 'reserved',
                'reserved_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMinutes(20),
                'tenant_id' => $tenantId,
                'created_at' => Carbon::now(),
            ]);

            // Clear cache
            Cache::forget("fashion_inventory:{$tenantId}:{$productId}");

            Log::info('Stock reserved successfully', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'order_id' => $orderId,
            ]);

            return true;
        });
    }

    /**
     * Release reserved stock
     */
    public function releaseReservedStock(int $productId, int $orderId, int $tenantId): bool
    {
        return DB::transaction(function () use ($productId, $orderId, $tenantId) {
            $reservation = DB::table('fashion_item_reserves')
                ->where('product_id', $productId)
                ->where('order_id', $orderId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'reserved')
                ->first();

            if (!$reservation) {
                return false;
            }

            // Release the stock
            DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->increment('available_stock', $reservation->quantity);

            DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->decrement('reserved_quantity', $reservation->quantity);

            // Update reservation status
            DB::table('fashion_item_reserves')
                ->where('id', $reservation->id)
                ->update([
                    'status' => 'released',
                    'released_at' => Carbon::now(),
                ]);

            // Clear cache
            Cache::forget("fashion_inventory:{$tenantId}:{$productId}");

            Log::info('Reserved stock released', [
                'product_id' => $productId,
                'order_id' => $orderId,
                'quantity' => $reservation->quantity,
            ]);

            return true;
        });
    }

    /**
     * Confirm stock after order completion
     */
    public function confirmStock(int $productId, int $orderId, int $tenantId): bool
    {
        return DB::transaction(function () use ($productId, $orderId, $tenantId) {
            $reservation = DB::table('fashion_item_reserves')
                ->where('product_id', $productId)
                ->where('order_id', $orderId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'reserved')
                ->first();

            if (!$reservation) {
                return false;
            }

            // Decrease reserved quantity (already decreased from available during reservation)
            DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->decrement('reserved_quantity', $reservation->quantity);

            // Update reservation status
            DB::table('fashion_item_reserves')
                ->where('id', $reservation->id)
                ->update([
                    'status' => 'confirmed',
                    'confirmed_at' => Carbon::now(),
                ]);

            // Clear cache
            Cache::forget("fashion_inventory:{$tenantId}:{$productId}");

            Log::info('Stock confirmed after order completion', [
                'product_id' => $productId,
                'order_id' => $orderId,
                'quantity' => $reservation->quantity,
            ]);

            return true;
        });
    }

    /**
     * Get low stock products for a store
     */
    public function getLowStockProducts(int $storeId, int $tenantId): array
    {
        return DB::table('fashion_products')
            ->where('fashion_store_id', $storeId)
            ->where('tenant_id', $tenantId)
            ->whereColumn('available_stock', '<=', 'low_stock_threshold')
            ->select('id', 'name', 'available_stock', 'low_stock_threshold')
            ->get()
            ->toArray();
    }

    /**
     * Update inventory forecast
     */
    public function updateInventoryForecast(int $productId, int $tenantId, int $forecastedDemand, string $restockDate): bool
    {
        try {
            DB::table('fashion_inventory_forecasts')->updateOrInsert(
                ['fashion_product_id' => $productId, 'tenant_id' => $tenantId],
                [
                    'forecasted_demand' => $forecastedDemand,
                    'restock_date' => $restockDate,
                    'updated_at' => Carbon::now(),
                ]
            );

            Log::info('Inventory forecast updated', [
                'product_id' => $productId,
                'tenant_id' => $tenantId,
                'forecasted_demand' => $forecastedDemand,
                'restock_date' => $restockDate,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update inventory forecast', [
                'product_id' => $productId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
