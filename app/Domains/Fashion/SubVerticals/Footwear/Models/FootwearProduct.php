<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class FootwearProduct extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'footwear_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'store_id',
        'name',
        'description',
        'sku',
        'brand',
        'color',
        'material',
        'sole_type',
        'season',
        'purpose',
        'price_b2c',
        'price_b2b',
        'old_price',
        'stock_quantity',
        'reserve_quantity',
        'images',
        'attributes',
        'status',
        'correlation_id',
        'tags',
        'metadata',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'is_featured',
        'is_new',
        'is_discounted',
        'weight_grams',
        'dimensions',
        'care_instructions',
        'origin_country',
        'warranty_months',
        'return_policy_days',
    ];

    protected $casts = [
        'images' => 'json',
        'attributes' => 'json',
        'tags' => 'json',
        'metadata' => 'json',
        'dimensions' => 'json',
        'price_b2c' => 'integer',
        'price_b2b' => 'integer',
        'old_price' => 'integer',
        'stock_quantity' => 'integer',
        'reserve_quantity' => 'integer',
        'weight_grams' => 'integer',
        'warranty_months' => 'integer',
        'return_policy_days' => 'integer',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_discounted' => 'boolean',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_OUT_OF_STOCK = 'out_of_stock';
    public const STATUS_DISCONTINUED = 'discontinued';

    public const SEASON_SPRING = 'spring';
    public const SEASON_SUMMER = 'summer';
    public const SEASON_AUTUMN = 'autumn';
    public const SEASON_WINTER = 'winter';
    public const SEASON_ALL_SEASON = 'all_season';

    public const PURPOSE_CASUAL = 'casual';
    public const PURPOSE_SPORTS = 'sports';
    public const PURPOSE_FORMAL = 'formal';
    public const PURPOSE_OUTDOOR = 'outdoor';
    public const PURPOSE_WORK = 'work';
    public const PURPOSE_RUNNING = 'running';
    public const PURPOSE_WALKING = 'walking';

    public const SOLE_TYPE_RUBBER = 'rubber';
    public const SOLE_TYPE_LEATHER = 'leather';
    public const SOLE_TYPE_SYNTHETIC = 'synthetic';
    public const SOLE_TYPE_EVA = 'eva';
    public const SOLE_TYPE_PU = 'pu';

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserve_quantity);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->available_stock > 0;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->old_price || $this->old_price <= $this->price_b2c) {
            return null;
        }

        return round((($this->old_price - $this->price_b2c) / $this->old_price) * 100, 1);
    }

    public function getAverageRatingAttribute(): float
    {
        return Cache::remember(
            "footwear_product:{$this->id}:avg_rating",
            now()->addHours(6),
            fn() => $this->reviews()->where('is_approved', true)->avg('rating') ?? 0.0
        );
    }

    public function getTotalReviewsAttribute(): int
    {
        return Cache::remember(
            "footwear_product:{$this->id}:total_reviews",
            now()->addHours(6),
            fn() => $this->reviews()->where('is_approved', true)->count()
        );
    }

    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '>', 'reserve_quantity');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('is_new', true);
    }

    public function scopeDiscounted(Builder $query): Builder
    {
        return $query->where('is_discounted', true)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'price_b2c');
    }

    public function scopeBySeason(Builder $query, string $season): Builder
    {
        return $query->where('season', $season);
    }

    public function scopeByPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }

    public function scopeByBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%")
                ->orWhere('tags', 'like', "%{$term}%");
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(FootwearStore::class, 'store_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(FootwearVariant::class, 'footwear_product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FootwearReview::class, 'footwear_product_id');
    }

    public function threeDAssets(): MorphMany
    {
        return $this->morphMany(\App\Models\Product3DAsset::class, 'product');
    }

    public function virtualTryOnAssets(): MorphMany
    {
        return $this->morphMany(\App\Models\VirtualTryOnAsset::class, 'product');
    }

    public function reserveStock(int $quantity): bool
    {
        if ($this->available_stock < $quantity) {
            Log::warning('Insufficient stock for reservation', [
                'product_id' => $this->id,
                'requested' => $quantity,
                'available' => $this->available_stock,
            ]);
            return false;
        }

        $this->increment('reserve_quantity', $quantity);
        $this->clearCache();
        return true;
    }

    public function releaseStock(int $quantity): void
    {
        $this->decrement('reserve_quantity', min($quantity, $this->reserve_quantity));
        $this->clearCache();
    }

    public function confirmStockReservation(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);
        $this->decrement('reserve_quantity', min($quantity, $this->reserve_quantity));
        $this->clearCache();
    }

    public function updateStock(int $quantity): void
    {
        $this->update(['stock_quantity' => $quantity]);
        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget("footwear_product:{$this->id}:avg_rating");
        Cache::forget("footwear_product:{$this->id}:total_reviews");
        Cache::tags(['footwear_products', "footwear_product:{$this->id}"])->flush();
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
            if ($model->isDirty('price_b2c')) {
                $newPrice = (int) $model->price_b2c;
                $oldPrice = (int) $model->getOriginal('price_b2c');

                if ($newPrice < $oldPrice) {
                    $model->old_price = $oldPrice;
                    $model->is_discounted = true;
                } elseif ($newPrice > $oldPrice) {
                    $model->old_price = null;
                    $model->is_discounted = false;
                }
            }

            if ($model->isDirty(['stock_quantity', 'reserve_quantity'])) {
                $model->clearCache();
            }
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
}
