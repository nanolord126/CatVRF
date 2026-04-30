<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\B2BLead;
use Modules\CatCRM\Domain\Entities\B2BDeal;
use Modules\Supermarket\Domain\Entities\Warehouse;
use Illuminate\Database\DatabaseManager;

final class CRMWarehouseIntegrationService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}
    public function linkLeadToWarehouse(B2BLead $lead, int $warehouseId): bool
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        if ($warehouse->tenant_id !== $lead->tenant_id) {
            throw new \InvalidArgumentException('Warehouse belongs to different tenant');
        }

        return $lead->linkWarehouse($warehouseId);
    }

    public function getAvailableWarehouses(int $tenantId, string $vertical): \Illuminate\Database\Eloquent\Collection
    {
        return Warehouse::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('vertical', $vertical)
            ->where('capacity_used', '<', $this->db->raw('capacity'))
            ->get();
    }

    public function reserveInventoryForDeal(B2BDeal $deal, array $items): bool
    {
        if (!$deal->warehouse_id) {
            throw new \InvalidArgumentException('Deal has no warehouse assigned');
        }

        return $this->db->transaction(function () use ($deal, $items) {
            foreach ($items as $item) {
                $stock = \Modules\Inventory\Domain\Entities\Stock::where('warehouse_id', $deal->warehouse_id)
                    ->where('sku', $item['sku'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for SKU: {$item['sku']}");
                }

                $stock->decrement('quantity', $item['quantity']);
                $stock->increment('reserved_quantity', $item['quantity']);
            }

            $deal->metadata = array_merge($deal->metadata ?? [], [
                'inventory_reserved' => true,
                'reservation_date' => now()->toIso8601String(),
            ]);
            
            return $deal->save();
        });
    }

    public function releaseReservedInventory(B2BDeal $deal): bool
    {
        if (!$deal->warehouse_id || empty($deal->metadata['inventory_reserved'] ?? false)) {
            return true;
        }

        return $this->db->transaction(function () use ($deal) {
            // Release reserved inventory
            // Implementation depends on Inventory module structure
            
            $deal->metadata = array_merge($deal->metadata, [
                'inventory_released' => true,
                'release_date' => now()->toIso8601String(),
            ]);
            
            return $deal->save();
        });
    }
}
