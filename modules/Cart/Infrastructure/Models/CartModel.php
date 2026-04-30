<?php

declare(strict_types=1);

namespace Modules\Cart\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Cart\Domain\Entities\Cart;

class CartModel extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'seller_id',
        'status',
        'reserved_until',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'reserved_until' => 'immutable_datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CartItemModel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function toDomain(): Cart
    {
        return new Cart(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            userId: $this->user_id,
            sellerId: $this->seller_id,
            status: $this->status,
            reservedUntil: $this->reserved_until,
            metadata: $this->metadata,
            correlationId: $this->correlation_id,
            createdAt: $this->created_at->toImmutable(),
            updatedAt: $this->updated_at->toImmutable(),
        );
    }
}
