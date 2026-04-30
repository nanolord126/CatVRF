<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Product — косметика и товары для продажи в бьюти-салоне.
 * 
 * Хранит информацию о продуктах, их ценах, остатках и категории.
 */
final class Product extends Model
{
    use TenantScoped;

    protected $table = 'beauty_products';

    protected $fillable = [
        'tenant_id',
        'salon_id',
        'category_id',
        'name',
        'description',
        'brand',
        'sku',
        'price_b2c',
        'price_b2b',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'is_active',
        'image_path',
        'metadata',
    ];

    protected $casts = [
        'price_b2c' => 'decimal:2',
        'price_b2b' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(BeautyService::class, 'beauty_service_products');
    }

    /**
     * Проверить, является ли товар низкоостаточным
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * Проверить, доступен ли товар
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->stock_quantity > 0;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
