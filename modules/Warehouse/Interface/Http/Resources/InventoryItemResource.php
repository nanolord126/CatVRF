<?php

declare(strict_types=1);

namespace Modules\Warehouse\Interface\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Warehouse\Domain\Entities\InventoryItem;

/**
 * Inventory Item Resource
 *
 * @property InventoryItem $resource
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class InventoryItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId()->toString(),
            'warehouse_id' => $this->resource->getWarehouseId()->toString(),
            'zone_id' => $this->resource->getZoneId()?->toString(),
            'product_sku' => $this->resource->getProductSku(),
            'product_name' => $this->resource->getProductName(),
            'quantity' => $this->resource->getQuantity(),
            'reserved_quantity' => $this->resource->getReservedQuantity(),
            'available_quantity' => $this->resource->getAvailableQuantity(),
            'order_type' => $this->resource->getOrderType()?->value,
            'branch_id' => $this->resource->getBranchId(),
            'is_shared' => $this->resource->isShared(),
            'is_b2b' => $this->resource->isB2B(),
            'is_b2c' => $this->resource->isB2C(),
            'created_at' => $this->resource->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->resource->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
