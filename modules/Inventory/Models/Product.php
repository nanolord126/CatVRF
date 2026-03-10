<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'description',
        'unit',
        'stock',
        'min_stock',
        'category',
        'price',
        'is_consumable',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function inventoryChecks(): HasMany
    {
        return $this->hasMany(InventoryCheckItem::class);
    }
}
