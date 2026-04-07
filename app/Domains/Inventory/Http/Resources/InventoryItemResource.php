<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API-ресурс для InventoryItem.
 *
 * Всегда возвращает correlation_id в ответе (CANON).
 *
 * @mixin \App\Domains\Inventory\Models\InventoryItem
 */
final class InventoryItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'warehouse_id'    => $this->warehouse_id,
            'product_id'      => $this->product_id,
            'quantity'         => $this->quantity,
            'reserved'        => $this->reserved,
            'available'       => $this->available,
            'cost_price'      => $this->cost_price,
            'tenant_id'       => $this->tenant_id,
            'correlation_id'  => $this->correlation_id ?? $request->header('X-Correlation-ID'),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
