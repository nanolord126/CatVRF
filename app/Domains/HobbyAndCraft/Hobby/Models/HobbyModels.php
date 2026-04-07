<?php declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HobbyDomainTrait extends Model
{
    use HasFactory;


    public static function bootHobbyDomainTrait(): void
        {
            static::creating(function (Model $model) {
                if (!$model->uuid) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (!$model->tenant_id && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('hobby_tenant_scope', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }
    }

    /**
     * HobbyStore Model
     * Represents a business entity (store) within the Hobby & Craft domain.
     */
    final class HobbyStore extends Model
    {
        use SoftDeletes, HobbyDomainTrait;

        protected $table = 'hobby_stores';

        protected $fillable = [
            'uuid', 'tenant_id', 'name', 'slug', 'description',
            'contact_email', 'website_url', 'settings', 'is_active', 'correlation_id'
        ];

        protected $casts = [
            'settings' => 'json',
            'is_active' => 'boolean',
        ];

        public function products(): HasMany
        {
            return $this->hasMany(HobbyProduct::class, 'store_id');
        }

        public function tutorials(): HasMany
        {
            return $this->hasMany(HobbyTutorial::class, 'store_id');
        }
    }

    /**
     * HobbyCategory Model
     */
    final class HobbyCategory extends Model
    {
        use HobbyDomainTrait;

        protected $table = 'hobby_categories';

        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'icon', 'meta'];

        protected $casts = ['meta' => 'json'];

        public function products(): HasMany
        {
            return $this->hasMany(HobbyProduct::class, 'category_id');
        }
    }

    /**
     * HobbyProduct Model
     */
    final class HobbyProduct extends Model
    {
        use SoftDeletes, HobbyDomainTrait;

        protected $table = 'hobby_products';

        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'category_id', 'title', 'sku', 'description',
            'price_b2c', 'price_b2b', 'stock_quantity', 'skill_level', 'images', 'tags',
            'is_active', 'correlation_id'
        ];

        protected $casts = [
            'images' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(HobbyStore::class, 'store_id');
        }

        public function category(): BelongsTo
        {
            return $this->belongsTo(HobbyCategory::class, 'category_id');
        }

        public function reviews(): MorphMany
        {
            return $this->morphMany(HobbyReview::class, 'reviewable');
        }
    }

    /**
     * HobbyTutorial Model
     */
    final class HobbyTutorial extends Model
    {
        use HobbyDomainTrait;

        protected $table = 'hobby_tutorials';

        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'title', 'content_html', 'video_url',
            'price', 'skill_level', 'required_product_ids', 'is_published', 'correlation_id'
        ];

        protected $casts = [
            'required_product_ids' => 'json',
            'is_published' => 'boolean',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(HobbyStore::class, 'store_id');
        }
    }

    /**
     * HobbyReview Model
     */
    final class HobbyReview extends Model
    {
        use HobbyDomainTrait;

        protected $table = 'hobby_reviews';

        protected $fillable = [
            'uuid', 'tenant_id', 'user_id', 'reviewable_type', 'reviewable_id',
            'rating', 'comment', 'media', 'correlation_id'
        ];

        protected $casts = ['media' => 'json'];

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function reviewable(): \Illuminate\Database\Eloquent\Relations\MorphTo
        {
            return $this->morphTo();
        }
}
