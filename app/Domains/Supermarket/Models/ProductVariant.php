<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductVariant extends Model
{
    protected $table = 'supermarket_product_variants';

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price_modifier',
        'weight',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'price_modifier' => 'integer',
        'weight' => 'decimal:2',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
