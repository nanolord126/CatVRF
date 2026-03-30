<?php declare(strict_types=1);

namespace App\Domains\Gardening\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GardeningDomainTrait extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static function booted(): void
        {
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id) && request()->hasHeader('X-Correlation-ID')) {
                    $model->correlation_id = request()->header('X-Correlation-ID');
                }
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (auth()->check() && auth()->user()->tenant_id) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }
    }

    /**
     * GardenStore Model
     */
    final class GardenStore extends Model
    {
        use GardeningDomainTrait;
        protected $table = 'garden_stores';
        protected $fillable = ['tenant_id', 'name', 'location_lat_lon', 'climate_zones', 'tags'];
        protected $casts = ['climate_zones' => 'json', 'tags' => 'json'];

        public function products(): HasMany
        {
            return $this->hasMany(GardenProduct::class, 'store_id');
        }
    }

    /**
     * GardenCategory Model
     */
    final class GardenCategory extends Model
    {
        use GardeningDomainTrait;
        protected $table = 'garden_categories';
        protected $fillable = ['tenant_id', 'name', 'slug', 'care_guide_summary'];

        public function products(): HasMany
        {
            return $this->hasMany(GardenProduct::class, 'category_id');
        }
    }

    /**
     * GardenProduct Model
     */
    final class GardenProduct extends Model
    {
        use GardeningDomainTrait;
        protected $table = 'garden_products';
        protected $fillable = [
            'tenant_id', 'store_id', 'category_id', 'name', 'sku',
            'price_b2c', 'price_b2b', 'stock_quantity', 'specifications', 'is_published'
        ];
        protected $casts = ['specifications' => 'json', 'is_published' => 'boolean'];

        public function category(): BelongsTo
        {
            return $this->belongsTo(GardenCategory::class, 'category_id');
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(GardenStore::class, 'store_id');
        }

        public function plant(): HasOne
        {
            return $this->hasOne(GardenPlant::class, 'product_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(GardenReview::class, 'product_id');
        }
    }

    /**
     * GardenPlant Model (The "Biological" Extension)
     */
    final class GardenPlant extends Model
    {
        use GardeningDomainTrait;
        protected $table = 'garden_plants';
        protected $fillable = [
            'tenant_id', 'product_id', 'botanical_name', 'hardiness_zone',
            'light_requirement', 'water_needs', 'care_calendar',
            'is_seedling', 'sowing_start', 'harvest_start'
        ];
        protected $casts = [
            'care_calendar' => 'json',
            'is_seedling' => 'boolean',
            'sowing_start' => 'date',
            'harvest_start' => 'date'
        ];

        public function product(): BelongsTo
        {
            return $this->belongsTo(GardenProduct::class, 'product_id');
        }
    }

    /**
     * GardenSubscriptionBox Model
     */
    final class GardenSubscriptionBox extends Model
    {
        use GardeningDomainTrait;
        protected $table = 'garden_subscription_boxes';
        protected $fillable = ['tenant_id', 'name', 'frequency', 'price', 'contents_json', 'is_active'];
        protected $casts = ['contents_json' => 'json', 'is_active' => 'boolean'];
    }

    /**
     * GardenReview Model
     */
    final class GardenReview extends Model
    {
        use GardeningDomainTrait;
        protected $table = 'garden_reviews';
        protected $fillable = ['tenant_id', 'product_id', 'user_id', 'rating', 'comment', 'growth_updates'];
        protected $casts = ['growth_updates' => 'json'];

        public function product(): BelongsTo
        {
            return $this->belongsTo(GardenProduct::class, 'product_id');
        }
}
