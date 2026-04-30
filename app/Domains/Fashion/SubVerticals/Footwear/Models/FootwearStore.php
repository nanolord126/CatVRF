<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

final class FootwearStore extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'footwear_stores';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'description',
        'slug',
        'logo',
        'banner',
        'address',
        'city',
        'country',
        'postal_code',
        'phone',
        'email',
        'website',
        'social_links',
        'rating',
        'total_reviews',
        'is_active',
        'is_verified',
        'is_featured',
        'business_type',
        'founded_year',
        'brands_carried',
        'specializations',
        'shipping_policy',
        'return_policy',
        'payment_methods',
        'working_hours',
        'timezone',
        'correlation_id',
        'metadata',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    protected $casts = [
        'social_links' => 'json',
        'rating' => 'float',
        'total_reviews' => 'integer',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'founded_year' => 'integer',
        'brands_carried' => 'json',
        'specializations' => 'json',
        'payment_methods' => 'json',
        'working_hours' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const BUSINESS_TYPE_RETAILER = 'retailer';
    public const BUSINESS_TYPE_BRAND = 'brand';
    public const BUSINESS_TYPE_MARKETPLACE = 'marketplace';
    public const BUSINESS_TYPE_DISTRIBUTOR = 'distributor';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    public function scopeByBusinessType(Builder $query, string $type): Builder
    {
        return $query->where('business_type', $type);
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->orderBy('rating', 'desc');
    }

    public function scopeMostReviewed(Builder $query): Builder
    {
        return $query->orderBy('total_reviews', 'desc');
    }

    public function products(): HasMany
    {
        return $this->hasMany(FootwearProduct::class, 'store_id');
    }

    public function getActiveProductsCountAttribute(): int
    {
        return Cache::remember(
            "footwear_store:{$this->id}:active_products_count",
            now()->addHours(6),
            fn() => $this->products()->active()->count()
        );
    }

    public function getTotalStockAttribute(): int
    {
        return Cache::remember(
            "footwear_store:{$this->id}:total_stock",
            now()->addHours(6),
            fn() => $this->products()->sum('stock_quantity')
        );
    }

    public function getAverageProductRatingAttribute(): float
    {
        return Cache::remember(
            "footwear_store:{$this->id}:avg_product_rating",
            now()->addHours(6),
            fn() => $this->products()->withAvg('reviews as avg_rating', 'rating')
                ->get()
                ->avg('avg_rating') ?? 0.0
        );
    }

    public function getRatingStarsAttribute(): array
    {
        $fullStars = floor($this->rating);
        $hasHalfStar = ($this->rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

        return [
            'full' => $fullStars,
            'half' => $hasHalfStar ? 1 : 0,
            'empty' => $emptyStars,
        ];
    }

    public function clearCache(): void
    {
        Cache::forget("footwear_store:{$this->id}:active_products_count");
        Cache::forget("footwear_store:{$this->id}:total_stock");
        Cache::forget("footwear_store:{$this->id}:avg_product_rating");
        Cache::tags(['footwear_stores', "footwear_store:{$this->id}"])->flush();
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
            if (empty($model->slug)) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }
            if ($model->isDirty(['rating', 'total_reviews'])) {
                $model->clearCache();
            }
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
}
