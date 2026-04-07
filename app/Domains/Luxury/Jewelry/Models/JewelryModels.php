<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class JewelryDomainTrait extends Model
{
    use HasFactory;

    protected static function booted_disabled(): void
        {
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()->id ?? 0;
                }
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant')) {
                    $builder->where('tenant_id', tenant()->id ?? 0);
                }
            });
        }

        public function getRouteKeyName(): string
        {
            return 'uuid';
        }
    }

    /**
     * JewelryStore (Layer 1/9)
     */
    final class JewelryStore extends Model
    {
        use JewelryDomainTrait, SoftDeletes;

        protected $table = 'jewelry_stores';
        protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'name', 'license_number', 'settings', 'tags', 'correlation_id'];
        protected $casts = [
            'settings' => 'array',
            'tags' => 'array',
            'deleted_at' => 'datetime',
        ];

        public function products(): HasMany
        {
            return $this->hasMany(JewelryProduct::class, 'store_id');
        }

        public function collections(): HasMany
        {
            return $this->hasMany(JewelryCollection::class, 'store_id');
        }

        public function customOrders(): HasMany
        {
            return $this->hasMany(JewelryCustomOrder::class, 'store_id');
        }
    }

    /**
     * JewelryCategory (Layer 1/9)
     */
    final class JewelryCategory extends Model
    {
        use JewelryDomainTrait;

        protected $table = 'jewelry_categories';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'sort_order', 'correlation_id'];

        public function products(): HasMany
        {
            return $this->hasMany(JewelryProduct::class, 'category_id');
        }
    }

    /**
     * JewelryCollection (Layer 1/9)
     */
    final class JewelryCollection extends Model
    {
        use JewelryDomainTrait;

        protected $table = 'jewelry_collections';
        protected $fillable = ['uuid', 'tenant_id', 'store_id', 'name', 'description', 'theme_data', 'correlation_id'];
        protected $casts = ['theme_data' => 'array'];

        public function store(): BelongsTo
        {
            return $this->belongsTo(JewelryStore::class, 'store_id');
        }

        public function products(): HasMany
        {
            return $this->hasMany(JewelryProduct::class, 'collection_id');
        }
    }

    /**
     * JewelryProduct (Layer 1/9)
     */
    final class JewelryProduct extends Model
    {
        use JewelryDomainTrait, SoftDeletes;

        protected $table = 'jewelry_products';
        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'category_id', 'collection_id', 'name', 'sku', 'description',
            'price_b2c', 'price_b2b', 'stock_quantity', 'metal_type', 'metal_fineness', 'weight_grams', 'gemstones',
            'has_certification', 'certificate_number', 'is_customizable', 'is_gift_wrapped', 'is_published', 'tags', 'correlation_id'
        ];
        protected $casts = [
            'gemstones' => 'array',
            'tags' => 'array',
            'has_certification' => 'boolean',
            'is_customizable' => 'boolean',
            'is_gift_wrapped' => 'boolean',
            'is_published' => 'boolean',
            'weight_grams' => 'float',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(JewelryStore::class, 'store_id');
        }

        public function category(): BelongsTo
        {
            return $this->belongsTo(JewelryCategory::class, 'category_id');
        }

        public function collection(): BelongsTo
        {
            return $this->belongsTo(JewelryCollection::class, 'collection_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(JewelryReview::class, 'product_id');
        }
    }

    /**
     * JewelryCustomOrder (Layer 1/9)
     */
    final class JewelryCustomOrder extends Model
    {
        use JewelryDomainTrait;

        protected $table = 'jewelry_custom_orders';
        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'user_id', 'status', 'customer_name', 'customer_phone',
            'estimated_price', 'final_price', 'ai_specification', 'user_notes', 'reference_photo_path', 'correlation_id'
        ];
        protected $casts = [
            'ai_specification' => 'array',
            'estimated_price' => 'integer',
            'final_price' => 'integer',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(JewelryStore::class, 'store_id');
        }
    }

    /**
     * JewelryReview (Layer 1/9)
     */
    final class JewelryReview extends Model
    {
        use JewelryDomainTrait;

        protected $table = 'jewelry_reviews';
        protected $fillable = ['uuid', 'tenant_id', 'product_id', 'user_id', 'rating', 'comment', 'photos', 'is_verified_purchase', 'correlation_id'];
        protected $casts = [
            'photos' => 'array',
            'is_verified_purchase' => 'boolean',
            'rating' => 'integer',
        ];

        public function product(): BelongsTo
        {
            return $this->belongsTo(JewelryProduct::class, 'product_id');
        }
}
