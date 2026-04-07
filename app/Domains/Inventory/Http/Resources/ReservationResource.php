<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API-ресурс для Reservation.
 *
 * @mixin \App\Domains\Inventory\Models\Reservation
 */
final class ReservationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'inventory_id'    => $this->inventory_id,
            'cart_id'         => $this->cart_id,
            'order_id'        => $this->order_id,
            'quantity'         => $this->quantity,
            'expires_at'      => $this->expires_at?->toIso8601String(),
            'tenant_id'       => $this->tenant_id,
            'correlation_id'  => $this->correlation_id ?? $request->header('X-Correlation-ID'),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
