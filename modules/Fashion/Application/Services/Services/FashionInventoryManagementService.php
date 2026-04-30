<?php

declare(strict_types=1);

namespace Modules\Fashion\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\LogManager;
use Carbon\Carbon;

final readonly class FashionInventoryManagementService
{
    use WithAuditLogging;

    private const CACHE_TTL = 1800;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Repository $cache,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
        private readonly GeoLogisticsAdapter $geoAdapter,
    ) {}

    /**
     * Get inventory status for a product
     */
    public function getProductInventoryStatus(int $productId, int $tenantId): array
    {
        $cacheKey = "fashion_inventory:{$tenantId}:{$productId}";

        return $this->cache->tags(['fashion', 'inventory', "tenant:{$tenantId}", "product:{$productId}"])
            ->remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($productId, $tenantId) {
            $product = $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $product) {
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
     * Reserve stock for an order
     */
    public function reserveStock(int $productId, int $quantity, int $orderId, int $tenantId): bool
    {
        return $this->db->transaction(function () use ($productId, $quantity, $orderId, $tenantId) {
            $product = $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (! $product || $product->available_stock < $quantity) {
                $this->log->warning('Insufficient stock for reservation', [
                    'product_id' => $productId,
                    'requested' => $quantity,
                    'available' => $product->available_stock ?? 0,
                    'order_id' => $orderId,
                ]);

                $this->logAction('fashion_stock_insufficient', 'FashionProduct', $productId, [
                    'requested' => $quantity,
                    'available' => $product->available_stock ?? 0,
                    'order_id' => $orderId,
                ], null, $tenantId);

                return false;
            }

            // Reserve the stock
            $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->decrement('available_stock', $quantity);

            $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->increment('reserved_quantity', $quantity);

            // Record the reservation
            $this->db->table('fashion_item_reserves')->insert([
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

            // Clear cacheags['', '', "tenant", "product]->forget($cacheKey)
            $this->cache->forget("fashion_inventory:{$tenantId}:{$productId}");

            $this->log->info('Stock reserved successfully', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'order_id' => $orderId,
            ]);

            $this->logAction('fashion_stock_reserved', 'FashionProduct', $productId, [
                'quantity' => $quantity,
                'order_id' => $orderId,
            ], null, $tenantId);

            return true;
        });
    }

    /**
     * Release reserved stock
     */
    public function releaseReservedStock(int $productId, int $orderId, int $tenantId): bool
    {
        return $this->db->transaction(function () use ($productId, $orderId, $tenantId) {
            $reservation = $this->db->table('fashion_item_reserves')
                ->where('product_id', $productId)
                ->where('order_id', $orderId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'reserved')
                ->first();

            if (! $reservation) {
                return false;
            }

            // Release the stock
            $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->increment('available_stock', $reservation->quantity);

            $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->decrement('reserved_quantity', $reservation->quantity);

            // Update reservation status
            $this->db->table('fashion_item_reserves')
                ->where('id', $reservation->id)
                ->update([
                    'status' => 'released',
                    'released_at' => Carbon::now(),
                ]);

            // Clear cacheags['', '', "tenant", "product])->forget($cacheKey
            $this->cache->forget("fashion_inventory:{$tenantId}:{$productId}");

            $this->log->info('Reserved stock released', [
                'product_id' => $productId,
                'order_id' => $orderId,
                'quantity' => $reservation->quantity,
            ]);

            $this->logAction('fashion_stock_released', 'FashionProduct', $productId, [
                'order_id' => $orderId,
                'quantity' => $reservation->quantity,
            ], null, $tenantId);

            return true;
        });
    }

    /**
     * Confirm stock after order completion
     */
    public function confirmStock(int $productId, int $orderId, int $tenantId): bool
    {
        return $this->db->transaction(function () use ($productId, $orderId, $tenantId) {
            $reservation = $this->db->table('fashion_item_reserves')
                ->where('product_id', $productId)
                ->where('order_id', $orderId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'reserved')
                ->first();

            if (! $reservation) {
                return false;
            }

            // Decrease reserved quantity (already decreased from available during reservation)
            $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->decrement('reserved_quantity', $reservation->quantity);

            // Update reservation status
            $this->db->table('fashion_item_reserves')
                ->where('id', $reservation->id)
                ->update([
                    'status' => 'confirmed',
                    'confirmed_at' => Carbon::now(),
                ]);

            // Clear cachetas['', '', "tenant", "product])->forget($cacheKey
            $this->cache->forget("fashion_inventory:{$tenantId}:{$productId}");

            $this->log->info('Stock confirmed after order completion', [
                'product_id' => $productId,
                'order_id' => $orderId,
                'quantity' => $reservation->quantity,
            ]);

            $this->logAction('fashion_stock_confirmed', 'FashionProduct', $productId, [
                'order_id' => $orderId,
                'quantity' => $reservation->quantity,
            ], null, $tenantId);

            return true;
        });
    }

    /**
     * Get low stock products for a store
     */
    public function getLowStockProducts(int $storeId, int $tenantId): array
    {
        return $this->db->table('fashion_products')
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
            $this->db->table('fashion_inventory_forecasts')->updateOrInsert(
                ['fashion_product_id' => $productId, 'tenant_id' => $tenantId],
                [
                    'forecasted_demand' => $forecastedDemand,
                    'restock_date' => $restockDate,
                    'updated_at' => Carbon::now(),
                ]
            );

            $this->log->info('Inventory forecast updated', [
                'product_id' => $productId,
                'tenant_id' => $tenantId,
                'forecasted_demand' => $forecastedDemand,
                'restock_date' => $restockDate,
            ]);

            $this->logAction('fashion_inventory_forecast_updated', 'FashionProduct', $productId, [
                'forecasted_demand' => $forecastedDemand,
                'restock_date' => $restockDate,
            ], null, $tenantId);

            return true;
        } catch (\Exception $e) {
            $this->log->error('Failed to update inventory forecast', [
                'product_id' => $productId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            $this->logAction('fashion_inventory_forecast_failed', 'FashionProduct', $productId, [
                'error' => $e->getMessage(),
            ], null, $tenantId);

            return false;
        }
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
        $restock = $this->db->table('fashion_inventory_forecasts')
            ->where('fashion_product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('restock_date', '>', Carbon::now())
            ->orderBy('restock_date')
            ->first();

        return $restock ? $restock->restock_date->toDateString() : null;
    }

    /**
     * Calculate delivery cost for Fashion order (basic zone/cost calculation)
     * Supports luxury items, footwear with try-on, and standard fashion delivery
     */
    public function calculateDeliveryCost(array $orderData): array
    {
        $vertical = $orderData['vertical'] ?? 'fashion';
        $subVertical = $orderData['sub_vertical'] ?? null;

        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => $vertical,
            'sub_vertical' => $subVertical,
            'seller_address' => $orderData['store_address'] ?? 'main_warehouse',
            'buyer_address' => $orderData['delivery_address'],
            'items' => $orderData['items'] ?? [],
        ]);

        // Fashion-specific adjustments
        $isLuxury = $subVertical === 'luxury';
        $isFootwear = $subVertical === 'footwear';
        $hasTryOn = $orderData['try_on_requested'] ?? false;

        // Premium for luxury items
        if ($isLuxury) {
            $deliveryCalculation['cost'] = (int) ceil($deliveryCalculation['cost'] * 1.5);
        }

        // Additional fee for footwear try-on (requires return logistics)
        if ($isFootwear && $hasTryOn) {
            $deliveryCalculation['cost'] += 500; // Additional 500 kopeki for return logistics
        }

        $this->log->info('Fashion delivery cost calculated', [
            'vertical' => $vertical,
            'sub_vertical' => $subVertical,
            'delivery_cost' => $deliveryCalculation['cost'],
            'is_luxury' => $isLuxury,
            'has_try_on' => $hasTryOn,
        ]);

        return $deliveryCalculation;
    }

    /**
     * Check if address is in Fashion delivery zone
     */
    public function isAddressInDeliveryZone(string $address, ?string $subVertical = null): bool
    {
        return $this->geoAdapter->isAddressInDeliveryZone($address, 'fashion');
    }
}
