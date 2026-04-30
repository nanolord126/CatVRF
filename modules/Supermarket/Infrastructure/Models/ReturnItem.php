<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $table = 'supermarket_return_items';

    protected $fillable = [
        'return_id',
        'order_item_id',
        'product_id',
        'quantity',
        'price_per_unit',
        'refund_amount',
        'condition',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(Return::class, 'return_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(\Modules\Supermarket\Infrastructure\Models\OrderItem::class, 'order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Supermarket\Infrastructure\Models\Product::class, 'product_id');
    }

    public function scopeSpoiled($query)
    {
        return $query->where('condition', 'spoiled');
    }

    public function scopeDamaged($query)
    {
        return $query->where('condition', 'damaged');
    }

    public function scopeGood($query)
    {
        return $query->where('condition', 'good');
    }
}
