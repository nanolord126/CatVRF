<?php

declare(strict_types=1);

namespace Modules\Cart\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Cart\Domain\Entities\CartItem;

class CartItemModel extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = [
        'uuid',
        'cart_id',
        'product_id',
        'quantity',
        'price_at_add',
        'current_price',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'price_at_add' => 'integer',
        'current_price' => 'integer',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(CartModel::class);
    }

    public function toDomain(): CartItem
    {
        return new CartItem(
            id: $this->id,
            uuid: $this->uuid,
            cartId: $this->cart_id,
            productId: $this->product_id,
            quantity: $this->quantity,
            priceAtAdd: $this->price_at_add,
            currentPrice: $this->current_price,
            metadata: $this->metadata,
            correlationId: $this->correlation_id,
            createdAt: $this->created_at->toImmutable(),
            updatedAt: $this->updated_at->toImmutable(),
        );
    }
}
