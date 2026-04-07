<?php declare(strict_types=1);

namespace App\Domains\Furniture\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureItem extends Model
{
    use HasFactory;

    /**
         * Boot the model to handle automatic UUID and tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            if (function_exists('tenant') && tenant()) {
                static::addGlobalScope('tenant_id', function ($builder) {
                    $builder->where('tenant_id', tenant()->id);
                });
            }
        }
    }

    /**
     * FurnitureStore Model
     */
    final class FurnitureStore extends Model
    {
        use FurnitureDomainTrait, SoftDeletes;

        protected $table = 'furniture_stores';

        protected $fillable = [
            'uuid', 'tenant_id', 'name', 'slug', 'address',
            'schedule_json', 'rating', 'is_verified',
            'correlation_id', 'tags'
        ];

        protected $casts = [
            'schedule_json' => 'json',
            'is_verified' => 'boolean',
            'tags' => 'json',
            'rating' => 'float',
        ];

        public function products(): HasMany
        {
            return $this->hasMany(FurnitureProduct::class);
        }
    }

    /**
     * FurnitureCategory Model
     */
    final class FurnitureCategory extends Model
    {
        use FurnitureDomainTrait;

        protected $table = 'furniture_categories';

        protected $fillable = [
            'uuid', 'tenant_id', 'name', 'slug', 'description',
            'sort_order', 'correlation_id'
        ];

        public function products(): HasMany
        {
            return $this->hasMany(FurnitureProduct::class);
        }
    }

    /**
     * FurnitureRoomType Model
     */
    final class FurnitureRoomType extends Model
    {
        protected $table = 'furniture_room_types';

        protected $fillable = ['uuid', 'name', 'slug', 'style_presets'];

        protected $casts = [
            'style_presets' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(fn ($model) => $model->uuid = (string) Str::uuid());
        }
    }

    /**
     * FurnitureProduct Model
     * The core entity of the Furniture vertical.
     */
    final class FurnitureProduct extends Model
    {
        use FurnitureDomainTrait, SoftDeletes;

        protected $table = 'furniture_products';

        protected $fillable = [
            'uuid', 'tenant_id', 'furniture_store_id', 'furniture_category_id',
            'name', 'sku', 'description', 'properties',
            'price_b2c', 'price_b2b', 'stock_quantity', 'hold_stock',
            'is_oversized', 'requires_assembly', 'assembly_cost',
            'threed_preview_url', 'recommended_room_types',
            'correlation_id', 'tags'
        ];

        protected $casts = [
            'properties' => 'json',
            'price_b2c' => 'integer',
            'price_b2b' => 'integer',
            'assembly_cost' => 'integer',
            'is_oversized' => 'boolean',
            'requires_assembly' => 'boolean',
            'recommended_room_types' => 'json',
            'tags' => 'json',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(FurnitureStore::class, 'furniture_store_id');
        }

        public function category(): BelongsTo
        {
            return $this->belongsTo(FurnitureCategory::class, 'furniture_category_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(FurnitureReview::class);
        }

        /**
         * Get price for specific customer type (B2C/B2B) in Rubles for UI.
         */
        public function getPriceInRubles(bool $isB2B = false): float
        {
            $cents = $isB2B ? $this->price_b2b : $this->price_b2c;
            return (float) ($cents / 100);
        }
    }

    /**
     * FurnitureCustomOrder Model
     */
    final class FurnitureCustomOrder extends Model
    {
        use FurnitureDomainTrait;

        protected $table = 'furniture_custom_orders';

        protected $fillable = [
            'uuid', 'tenant_id', 'user_id', 'room_type_id',
            'status', 'total_amount', 'ai_specification',
            'room_photo_analysis', 'include_assembly', 'correlation_id'
        ];

        protected $casts = [
            'ai_specification' => 'json',
            'room_photo_analysis' => 'json',
            'include_assembly' => 'boolean',
            'total_amount' => 'integer',
        ];

        public function roomType(): BelongsTo
        {
            return $this->belongsTo(FurnitureRoomType::class);
        }
    }

    /**
     * FurnitureReview Model
     */
    final class FurnitureReview extends Model
    {
        use FurnitureDomainTrait;

        protected $table = 'furniture_reviews';

        protected $fillable = [
            'uuid', 'tenant_id', 'user_id', 'furniture_product_id',
            'rating', 'comment', 'photos', 'is_verified_purchase', 'correlation_id'
        ];

        protected $casts = [
            'photos' => 'json',
            'is_verified_purchase' => 'boolean',
            'rating' => 'integer',
        ];

        public function product(): BelongsTo
        {
            return $this->belongsTo(FurnitureProduct::class, 'furniture_product_id');
        }
}
