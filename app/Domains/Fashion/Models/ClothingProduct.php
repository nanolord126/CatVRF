<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ClothingProduct — Fashion vertical product model with material and size support.
 *
 * Extends the base FashionProduct with specific clothing features:
 * - Material composition with allergen tracking
 * - Size variants with fit types
 * - Season and care instructions
 * - Look builder support
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $fashion_store_id
 * @property string $name
 * @property string $description
 * @property string $sku
 * @property string $brand
 * @property string $color
 * @property array $materials List of material names
 * @property string $season spring, summer, autumn, winter, all_season
 * @property string $fit_type slim, regular, relaxed, oversized
 * @property string $gender male, female, unisex, kids
 * @property array $care_instructions Care instructions
 * @property int $price_b2c
 * @property int $price_b2b
 * @property int $old_price
 * @property int $stock_quantity
 * @property int $reserve_quantity
 * @property array $images
 * @property array $attributes Additional attributes
 * @property string $status
 * @property string $correlation_id
 * @property array $tags
 * @property bool $is_featured
 * @property bool $is_new
 * @property bool $is_sustainable
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class ClothingProduct extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'fashion_clothing_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'fashion_store_id',
        'name',
        'description',
        'sku',
        'brand',
        'color',
        'materials',
        'season',
        'fit_type',
        'gender',
        'care_instructions',
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
        'is_featured',
        'is_new',
        'is_sustainable',
    ];

    protected $casts = [
        'materials' => 'json',
        'care_instructions' => 'json',
        'images' => 'json',
        'attributes' => 'json',
        'tags' => 'json',
        'price_b2c' => 'integer',
        'price_b2b' => 'integer',
        'old_price' => 'integer',
        'stock_quantity' => 'integer',
        'reserve_quantity' => 'integer',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_sustainable' => 'boolean',
    ];

    protected $hidden = [
        'correlation_id',
    ];

    public const SEASON_SPRING = 'spring';
    public const SEASON_SUMMER = 'summer';
    public const SEASON_AUTUMN = 'autumn';
    public const SEASON_WINTER = 'winter';
    public const SEASON_ALL_SEASON = 'all_season';

    public const FIT_SLIM = 'slim';
    public const FIT_REGULAR = 'regular';
    public const FIT_RELAXED = 'relaxed';
    public const FIT_OVERSIZED = 'oversized';

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_UNISEX = 'unisex';
    public const GENDER_KIDS = 'kids';

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

    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
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

    public function scopeSustainable(Builder $query): Builder
    {
        return $query->where('is_sustainable', true);
    }

    public function scopeBySeason(Builder $query, string $season): Builder
    {
        return $query->where('season', $season);
    }

    public function scopeByGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    public function scopeByFitType(Builder $query, string $fitType): Builder
    {
        return $query->where('fit_type', $fitType);
    }

    public function scopeByBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }

    public function scopeByMaterial(Builder $query, string $material): Builder
    {
        return $query->whereJsonContains('materials', $material);
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
        return $this->belongsTo(FashionStore::class, 'fashion_store_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ClothingVariant::class, 'clothing_product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FashionReview::class, 'fashion_product_id');
    }

    /**
     * Reserve stock for order.
     */
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

    /**
     * Release reserved stock.
     */
    public function releaseStock(int $quantity): void
    {
        $this->decrement('reserve_quantity', min($quantity, $this->reserve_quantity));
        $this->clearCache();
    }

    /**
     * Confirm stock reservation (after order completion).
     */
    public function confirmStockReservation(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);
        $this->decrement('reserve_quantity', min($quantity, $this->reserve_quantity));
        $this->clearCache();
    }

    /**
     * Clear product cache.
     */
    public function clearCache(): void
    {
        Cache::tags(['clothing_products', "clothing_product:{$this->id}"])->flush();
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
            if ($model->isDirty('price_b2c')) {
                $newPrice = (int) $model->price_b2c;
                $oldPrice = (int) $model->getOriginal('price_b2c');

                if ($newPrice < $oldPrice) {
                    $model->old_price = $oldPrice;
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
