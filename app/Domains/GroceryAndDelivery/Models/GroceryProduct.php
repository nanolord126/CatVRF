<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GroceryProduct extends Model
{

    protected $table = 'grocery_products';

    protected $fillable = [
        'uuid', 'tenant_id', 'store_id', 'sku', 'name', 'category', 'price',
        'current_stock', 'min_stock', 'max_stock', 'barcode', 'description',
        'image_url', 'weight_kg', 'is_active', 'tags', 'correlation_id'
    ];

    protected $casts = [
        'price' => 'integer',
        'current_stock' => 'integer',
        'weight_kg' => 'float',
        'is_active' => 'boolean',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(GroceryStore::class, 'store_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(GroceryOrderItem::class, 'product_id');
    }
}
