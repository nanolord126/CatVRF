<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Flowers\Domain\Entities\OrderItem as OrderItemEntity;

final class OrderItemModel extends Model
{
    protected $table = 'flowers_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'discount_amount',
        'total_price',
        'customizations',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'customizations' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function photos(): BelongsTo
    {
        return $this->hasMany(PhotoModel::class, 'order_item_id');
    }

    public function toDomain(): OrderItemEntity
    {
        return new OrderItemEntity(
            id: $this->id,
            orderId: $this->order_id,
            productId: $this->product_id,
            productName: $this->product_name,
            quantity: $this->quantity,
            unitPrice: (float) $this->unit_price,
            discountAmount: (float) $this->discount_amount,
            totalPrice: (float) $this->total_price,
            customizations: $this->customizations,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(OrderItemEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'order_id' => $entity->orderId,
            'product_id' => $entity->productId,
            'product_name' => $entity->productName,
            'quantity' => $entity->quantity,
            'unit_price' => $entity->unitPrice,
            'discount_amount' => $entity->discountAmount,
            'total_price' => $entity->totalPrice,
            'customizations' => $entity->customizations,
        ]);
    }
}
