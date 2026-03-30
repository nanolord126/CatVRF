<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SportsNutritionDomainTrait extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static function booted(): void
        {
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()->id ?? 0;
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
                }
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }
    }

    /**
     * SportsNutritionStore Model (Layer 1/9).
     */
    final class SportsNutritionStore extends Model
    {
        use SoftDeletes, SportsNutritionDomainTrait;

        protected $table = 'sports_nutrition_stores';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'license_number', 'location_address', 'working_hours', 'tags', 'rating', 'correlation_id'];
        protected $casts = ['working_hours' => 'json', 'tags' => 'json', 'rating' => 'float'];

        public function products(): HasMany
        {
            return $this->hasMany(SportsNutritionProduct::class, 'store_id');
        }
    }

    /**
     * SportsNutritionCategory Model (Layer 1/9).
     */
    final class SportsNutritionCategory extends Model
    {
        use SportsNutritionDomainTrait;

        protected $table = 'sports_nutrition_categories';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'description', 'is_active'];

        public function products(): HasMany
        {
            return $this->hasMany(SportsNutritionProduct::class, 'category_id');
        }
    }

    /**
     * SportsNutritionProduct Model (Layer 1/9).
     */
    final class SportsNutritionProduct extends Model
    {
        use SoftDeletes, SportsNutritionDomainTrait;

        protected $table = 'sports_nutrition_products';
        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'category_id', 'name', 'sku', 'brand', 'description',
            'price_b2c', 'price_b2b', 'stock_quantity', 'form_factor', 'servings_count',
            'nutrition_facts', 'allergens', 'expiry_date', 'is_vegan', 'is_gmo_free',
            'is_published', 'tags', 'correlation_id'
        ];
        protected $casts = [
            'nutrition_facts' => 'json',
            'allergens' => 'json',
            'tags' => 'json',
            'expiry_date' => 'date',
            'is_vegan' => 'boolean',
            'is_gmo_free' => 'boolean',
            'is_published' => 'boolean',
            'price_b2c' => 'integer',
            'price_b2b' => 'integer',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(SportsNutritionStore::class, 'store_id');
        }

        public function category(): BelongsTo
        {
            return $this->belongsTo(SportsNutritionCategory::class, 'category_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(SportsNutritionReview::class, 'product_id');
        }
    }

    /**
     * SportsNutritionSubscriptionBox Model (Layer 1/9).
     */
    final class SportsNutritionSubscriptionBox extends Model
    {
        use SportsNutritionDomainTrait;

        protected $table = 'sports_nutrition_subscription_boxes';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'description', 'price_monthly', 'included_skus', 'training_goal', 'is_active', 'correlation_id'];
        protected $casts = ['included_skus' => 'json', 'price_monthly' => 'integer', 'is_active' => 'boolean'];

        public function getEstimatedNutritionAttribute(): array
        {
            // Composite attribute logic for UI
            return ['goal' => $this->training_goal, 'items_count' => count($this->included_skus ?? [])];
        }
    }

    /**
     * SportsNutritionConsumable Model (Layer 1/9).
     */
    final class SportsNutritionConsumable extends Model
    {
        use SportsNutritionDomainTrait;

        protected $table = 'sports_nutrition_consumables';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'stock_kg', 'min_threshold', 'purity_percentage', 'correlation_id'];
        protected $casts = ['stock_kg' => 'float', 'min_threshold' => 'float'];
    }

    /**
     * SportsNutritionReview Model (Layer 1/9).
     */
    final class SportsNutritionReview extends Model
    {
        use SportsNutritionDomainTrait;

        protected $table = 'sports_nutrition_reviews';
        protected $fillable = ['uuid', 'tenant_id', 'user_id', 'product_id', 'rating', 'comment', 'impact_data', 'is_verified_purchase', 'correlation_id'];
        protected $casts = ['impact_data' => 'json', 'is_verified_purchase' => 'boolean', 'rating' => 'integer'];

        public function product(): BelongsTo
        {
            return $this->belongsTo(SportsNutritionProduct::class, 'product_id');
        }
}
