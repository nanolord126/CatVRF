<?php declare(strict_types=1);

namespace App\Domains\Cart\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Cart\Models\Cart;

final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Cart $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'seller_id' => $this->seller_id,
            'status' => $this->status,
            'reserved_until' => $this->reserved_until?->format('Y-m-d H:i:s'),
            'is_expired' => $this->isExpired(),
            'is_active' => $this->isActive(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'total' => $this->when(isset($this->total), fn() => $this->total),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
