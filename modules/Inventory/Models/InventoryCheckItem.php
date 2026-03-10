<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCheckItem extends Model
{
    protected $fillable = [
        'inventory_check_id',
        'product_id',
        'expected_quantity',
        'actual_quantity',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryCheck(): BelongsTo
    {
        return $this->belongsTo(InventoryCheck::class);
    }
}
