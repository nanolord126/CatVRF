<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Flowers\Domain\Entities\ProductFlower as ProductFlowerEntity;

final class ProductFlowerModel extends Model
{
    protected $table = 'flowers_product_flowers';

    protected $fillable = [
        'product_id',
        'flower_id',
        'flower_name',
        'quantity',
        'unit',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'notes' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function flower(): BelongsTo
    {
        return $this->belongsTo(FlowerModel::class, 'flower_id');
    }

    public function toDomain(): ProductFlowerEntity
    {
        return new ProductFlowerEntity(
            id: $this->id,
            productId: $this->product_id,
            flowerId: $this->flower_id,
            flowerName: $this->flower_name,
            quantity: $this->quantity,
            unit: $this->unit,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ProductFlowerEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'product_id' => $entity->productId,
            'flower_id' => $entity->flowerId,
            'flower_name' => $entity->flowerName,
            'quantity' => $entity->quantity,
            'unit' => $entity->unit,
            'notes' => $entity->notes,
        ]);
    }
}
