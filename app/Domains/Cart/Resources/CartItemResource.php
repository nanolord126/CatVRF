<?php declare(strict_types=1);

namespace App\Domains\Cart\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Cart\Models\CartItem;

final class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CartItem $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'price_at_add' => $this->price_at_add,
            'current_price' => $this->current_price,
            'effective_price' => $this->getEffectivePrice(),
            'total' => $this->getTotal(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
