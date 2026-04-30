<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GroceryOrderItem extends Model
{
    protected $table = 'grocery_order_items';

    protected $fillable = [
        'tenant_id', 'order_id', 'product_id', 'quantity', 'price_per_unit', 'total_price', 'correlation_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_unit' => 'integer',
        'total_price' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(GroceryOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(GroceryProduct::class);
    }
}
