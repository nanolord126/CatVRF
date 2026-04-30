<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class UnifiedStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'sku',
        'name',
        'description',
        'category',
        'brand',
        'quantity',
        'reserved_quantity',
        'b2b_quantity',
        'b2c_quantity',
        'unit_price',
        'cost_price',
        'min_quantity',
        'max_quantity',
        'reorder_point',
        'batch_number',
        'expiry_date',
        'location',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'b2b_quantity' => 'integer',
        'b2c_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'reorder_point' => 'integer',
        'expiry_date' => 'datetime',
        'metadata' => 'json',
    ];

    protected $table = 'unified_stocks';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(UnifiedWarehouse::class, 'warehouse_id');
    }

    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function getAvailableForOrderType(string $orderType): int
    {
        return $orderType === 'b2b' ? $this->b2b_quantity : $this->b2c_quantity;
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_point;
    }

    public function isOverstocked(): bool
    {
        return $this->quantity >= $this->max_quantity;
    }
}
