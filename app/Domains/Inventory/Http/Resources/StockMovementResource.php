<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API-ресурс для StockMovement.
 *
 * @mixin \App\Domains\Inventory\Models\StockMovement
 */
final class StockMovementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'inventory_id'    => $this->inventory_id,
            'warehouse_id'    => $this->warehouse_id,
            'type'            => $this->type,
            'quantity'         => $this->quantity,
            'source_type'     => $this->source_type,
            'source_id'       => $this->source_id,
            'metadata'        => $this->metadata,
            'tenant_id'       => $this->tenant_id,
            'correlation_id'  => $this->correlation_id ?? $request->header('X-Correlation-ID'),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
