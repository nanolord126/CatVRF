<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Flowers\Domain\Entities\OrderModifier as OrderModifierEntity;

final class OrderModifierModel extends Model
{
    protected $table = 'flowers_order_modifiers';

    protected $fillable = [
        'order_id',
        'modifier_id',
        'modifier_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(ModifierModel::class, 'modifier_id');
    }

    public function toDomain(): OrderModifierEntity
    {
        return new OrderModifierEntity(
            id: $this->id,
            orderId: $this->order_id,
            modifierId: $this->modifier_id,
            modifierName: $this->modifier_name,
            quantity: $this->quantity,
            unitPrice: (float) $this->unit_price,
            totalPrice: (float) $this->total_price,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(OrderModifierEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'order_id' => $entity->orderId,
            'modifier_id' => $entity->modifierId,
            'modifier_name' => $entity->modifierName,
            'quantity' => $entity->quantity,
            'unit_price' => $entity->unitPrice,
            'total_price' => $entity->totalPrice,
        ]);
    }
}
