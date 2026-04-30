<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ClothingVariant — Size/color variant for clothing products.
 *
 * Supports:
 * - Size systems (EU, US, UK, International)
 * - Color variations
 * - Fit types
 * - Individual stock tracking per variant
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $clothing_product_id
 * @property string $sku_variant
 * @property string $color
 * @property string $color_code Hex color code
 * @property string $size_eu EU size
 * @property string $size_us US size
 * @property string $size_uk UK size
 * @property string $size_international XS, S, M, L, XL, etc.
 * @property string $fit_type slim, regular, relaxed, oversized
 * @property float $price_adjustment Price adjustment from base
 * @property int $current_stock
 * @property int $reserved_stock
 * @property array $images Variant-specific images
 * @property array $attributes Additional attributes
 * @property bool $is_active
 * @property bool $is_default Default variant for product
 * @property string $correlation_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class ClothingVariant extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'fashion_clothing_variants';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clothing_product_id',
        'sku_variant',
        'color',
        'color_code',
        'size_eu',
        'size_us',
        'size_uk',
        'size_international',
        'fit_type',
        'price_adjustment',
        'current_stock',
        'reserved_stock',
        'images',
        'attributes',
        'is_active',
        'is_default',
        'correlation_id',
    ];

    protected $casts = [
        'images' => 'json',
        'attributes' => 'json',
        'price_adjustment' => 'float',
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected $hidden = [
        'correlation_id',
    ];

    public const FIT_SLIM = 'slim';
    public const FIT_REGULAR = 'regular';
    public const FIT_RELAXED = 'relaxed';
    public const FIT_OVERSIZED = 'oversized';

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->current_stock - $this->reserved_stock);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->available_stock > 0 && $this->is_active;
    }

    public function getFinalPriceAttribute(): int
    {
        $basePrice = $this->product->price_b2c;
        return (int) ($basePrice + $this->price_adjustment);
    }

    public function getFinalPriceB2BAttribute(): int
    {
        $basePrice = $this->product->price_b2b;
        return (int) ($basePrice + $this->price_adjustment);
    }

    public function getSizeLabelAttribute(): string
    {
        return "EU {$this->size_eu} / US {$this->size_us} / UK {$this->size_uk} ({$this->size_international})";
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '>', 'reserved_stock')
            ->where('is_active', true);
    }

    public function scopeByColor(Builder $query, string $color): Builder
    {
        return $query->where('color', $color);
    }

    public function scopeBySize(Builder $query, string $sizeInternational): Builder
    {
        return $query->where('size_international', $sizeInternational);
    }

    public function scopeByFitType(Builder $query, string $fitType): Builder
    {
        return $query->where('fit_type', $fitType);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ClothingProduct::class, 'clothing_product_id');
    }

    /**
     * Reserve stock for order.
     */
    public function reserveStock(int $quantity): bool
    {
        if ($this->available_stock < $quantity) {
            Log::warning('Insufficient variant stock for reservation', [
                'variant_id' => $this->id,
                'product_id' => $this->clothing_product_id,
                'requested' => $quantity,
                'available' => $this->available_stock,
            ]);
            return false;
        }

        $this->increment('reserved_stock', $quantity);
        $this->clearCache();
        return true;
    }

    /**
     * Release reserved stock.
     */
    public function releaseStock(int $quantity): void
    {
        $this->decrement('reserved_stock', min($quantity, $this->reserved_stock));
        $this->clearCache();
    }

    /**
     * Confirm stock reservation (after order completion).
     */
    public function confirmStockReservation(int $quantity): void
    {
        $this->decrement('current_stock', $quantity);
        $this->decrement('reserved_stock', min($quantity, $this->reserved_stock));
        $this->clearCache();
    }

    /**
     * Update stock quantity.
     */
    public function updateStock(int $quantity): void
    {
        $this->update(['current_stock' => $quantity]);
        $this->clearCache();
    }

    /**
     * Clear variant cache.
     */
    public function clearCache(): void
    {
        Cache::tags(['clothing_variants', "clothing_variant:{$this->id}"])->flush();
        if ($this->product) {
            $this->product->clearCache();
        }
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = tenant()->id ?? auth()->user()?->tenant_id;
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty(['current_stock', 'reserved_stock'])) {
                $model->clearCache();
            }
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
}
