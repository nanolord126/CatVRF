<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class FootwearVariant extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'footwear_variants';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'footwear_product_id',
        'sku_variant',
        'color',
        'color_code',
        'size_eu',
        'size_us',
        'size_uk',
        'size_cm',
        'width',
        'price_adjustment',
        'current_stock',
        'reserved_stock',
        'images',
        'attributes',
        'is_active',
        'is_default',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'images' => 'json',
        'attributes' => 'json',
        'metadata' => 'json',
        'price_adjustment' => 'float',
        'size_cm' => 'float',
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const WIDTH_NARROW = 'narrow';
    public const WIDTH_REGULAR = 'regular';
    public const WIDTH_WIDE = 'wide';
    public const WIDTH_EXTRA_WIDE = 'extra_wide';

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
        return "EU {$this->size_eu} / US {$this->size_us} / UK {$this->size_uk} ({$this->size_cm} cm)";
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

    public function scopeBySize(Builder $query, string $sizeEu): Builder
    {
        return $query->where('size_eu', $sizeEu);
    }

    public function scopeByWidth(Builder $query, string $width): Builder
    {
        return $query->where('width', $width);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FootwearProduct::class, 'footwear_product_id');
    }

    public function threeDAssets(): MorphMany
    {
        return $this->morphMany(\App\Models\Product3DAsset::class, 'variant');
    }

    public function virtualTryOnAssets(): MorphMany
    {
        return $this->morphMany(\App\Models\VirtualTryOnAsset::class, 'variant');
    }

    public function reserveStock(int $quantity): bool
    {
        if ($this->available_stock < $quantity) {
            Log::warning('Insufficient variant stock for reservation', [
                'variant_id' => $this->id,
                'product_id' => $this->footwear_product_id,
                'requested' => $quantity,
                'available' => $this->available_stock,
            ]);
            return false;
        }

        $this->increment('reserved_stock', $quantity);
        $this->clearCache();
        return true;
    }

    public function releaseStock(int $quantity): void
    {
        $this->decrement('reserved_stock', min($quantity, $this->reserved_stock));
        $this->clearCache();
    }

    public function confirmStockReservation(int $quantity): void
    {
        $this->decrement('current_stock', $quantity);
        $this->decrement('reserved_stock', min($quantity, $this->reserved_stock));
        $this->clearCache();
    }

    public function updateStock(int $quantity): void
    {
        $this->update(['current_stock' => $quantity]);
        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::tags(['footwear_variants', "footwear_variant:{$this->id}"])->flush();
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
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) \Illuminate\Support\Str::uuid();
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
