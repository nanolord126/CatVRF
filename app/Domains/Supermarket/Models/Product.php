<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Product extends Model
{
    protected $table = 'supermarket_products';

    protected $fillable = [
        'tenant_id',
        'sub_vertical',
        'name',
        'description',
        'price',
        'weight',
        'requires_cold_chain',
        'shelf_life_days',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'weight' => 'decimal:2',
        'requires_cold_chain' => 'boolean',
        'shelf_life_days' => 'integer',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(\App\Domains\Inventory\Models\InventoryItem::class, 'product_id');
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(B2BPriceRule::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySubVertical($query, string $subVertical)
    {
        return $query->where('sub_vertical', $subVertical);
    }

    public function scopeRequiresColdChain($query)
    {
        return $query->where('requires_cold_chain', true);
    }
}
