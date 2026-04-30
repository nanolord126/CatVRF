<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\InventoryUpdated;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Inventory WebSocket Service
 *
 * Handles real-time inventory updates via WebSocket/Reverb:
 * - Broadcasts inventory changes to connected clients
 * - Manages subscription channels
 * - Provides real-time inventory status
 * - Handles connection management
 *
 * @author CatVRF Team
 * @version 2026.04.26
 */
final readonly class InventoryWebSocketService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Broadcast inventory update to all subscribers
     *
     * @param  int  $inventoryItemId
     * @param  int  $tenantId
     * @param  array  $changes
     * @return void
     */
    public function broadcastUpdate(int $inventoryItemId, int $tenantId, array $changes): void
    {
        try {
            broadcast(new InventoryUpdated($inventoryItemId, $tenantId, $changes));

            $this->logger->debug('Inventory update broadcasted', [
                'inventory_item_id' => $inventoryItemId,
                'tenant_id' => $tenantId,
                'changes' => $changes,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to broadcast inventory update', [
                'inventory_item_id' => $inventoryItemId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast low stock alert
     *
     * @param  InventoryItem  $item
     * @return void
     */
    public function broadcastLowStockAlert(InventoryItem $item): void
    {
        $changes = [
            'type' => 'low_stock_alert',
            'current_quantity' => $item->quantity,
            'min_stock_level' => $item->min_stock_level,
            'sku' => $item->sku,
            'name' => $item->name,
        ];

        $this->broadcastUpdate($item->id, $item->tenant_id, $changes);
    }

    /**
     * Broadcast stock out alert
     *
     * @param  InventoryItem  $item
     * @return void
     */
    public function broadcastStockOutAlert(InventoryItem $item): void
    {
        $changes = [
            'type' => 'stock_out_alert',
            'sku' => $item->sku,
            'name' => $item->name,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->broadcastUpdate($item->id, $item->tenant_id, $changes);
    }

    /**
     * Broadcast inventory replenished event
     *
     * @param  InventoryItem  $item
     * @param  int  $quantityAdded
     * @return void
     */
    public function broadcastReplenished(InventoryItem $item, int $quantityAdded): void
    {
        $changes = [
            'type' => 'inventory_replenished',
            'quantity_added' => $quantityAdded,
            'new_quantity' => $item->quantity,
            'sku' => $item->sku,
            'name' => $item->name,
        ];

        $this->broadcastUpdate($item->id, $item->tenant_id, $changes);
    }

    /**
     * Get real-time inventory status for multiple items
     *
     * @param  array<int>  $inventoryItemIds
     * @return array<int, array{quantity: int, reserved: int, available: int, version: int}>
     */
    public function getRealTimeStatus(array $inventoryItemIds): array
    {
        $cacheKey = 'inventory:realtime:' . md5(implode(',', $inventoryItemIds));

        return Cache::remember($cacheKey, 5, function () use ($inventoryItemIds) {
            $items = InventoryItem::whereIn('id', $inventoryItemIds)->get();

            return $items->mapWithKeys(function (InventoryItem $item) {
                return [
                    $item->id => [
                        'quantity' => $item->quantity,
                        'reserved' => $item->reserved,
                        'available' => $item->quantity - $item->reserved,
                        'version' => $item->version,
                        'sku' => $item->sku,
                    ],
                ];
            })->toArray();
        });
    }

    /**
     * Invalidate real-time cache for specific items
     *
     * @param  array<int>  $inventoryItemIds
     * @return void
     */
    public function invalidateRealTimeCache(array $inventoryItemIds): void
    {
        $cacheKey = 'inventory:realtime:' . md5(implode(',', $inventoryItemIds));
        Cache::forget($cacheKey);
    }

    /**
     * Get channel name for tenant inventory
     *
     * @param  int  $tenantId
     * @return string
     */
    public function getTenantChannel(int $tenantId): string
    {
        return "tenant.{$tenantId}.inventory";
    }

    /**
     * Get channel name for specific inventory item
     *
     * @param  int  $inventoryItemId
     * @return string
     */
    public function getItemChannel(int $inventoryItemId): string
    {
        return "inventory.{$inventoryItemId}";
    }
}
