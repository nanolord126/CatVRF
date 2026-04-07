<?php declare(strict_types=1);

namespace App\Domains\VeganProducts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class VeganProduct extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'vegan_products';

        /**
         * Check availability taking hold stock into account.
         */
        public function getIsAvailableAttribute(): bool
        {
            return ($this->current_stock - $this->hold_stock) > 0
                   && $this->availability_status === 'in_stock';
        }

        /**
         * Return B2B or B2C price based on client context.
         */
        public function getActivePrice(bool $isB2B = false): int
        {
            return ($isB2B && $this->is_b2b_available && $this->b2b_price)
                   ? $this->b2b_price
                   : $this->price;
        }

        public function scopeInStock(Builder $query): Builder
        {
            return $query->where('current_stock', '>', 0)->where('availability_status', 'in_stock');
        }

        public function scopeByAllergen(Builder $query, string $allergen): Builder
        {
            return $query->whereJsonContains('allergen_info', $allergen);
        }
    }

    /**
     * VeganStore Model - Physical and Virtual Points of Presence.
     */
    final class VeganStore extends Model
    {
        use HasFactory, SoftDeletes;
        protected $table = 'vegan_stores';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'address', 'schedule', 'certification_id', 'is_active', 'rating', 'correlation_id', 'tags'];
        protected $casts = ['schedule' => 'json', 'tags' => 'json', 'is_active' => 'boolean'];

        protected static function booted(): void
        {
            static::creating(fn ($m) => $m->uuid = $m->uuid ?: (string) Str::uuid());
            static::addGlobalScope('tenant', fn ($b) => tenant() ? $b->where('tenant_id', tenant()->id) : null);
        }

        public function products(): HasMany { return $this->hasMany(VeganProduct::class, 'vegan_store_id'); }
    }

    /**
     * VeganCategory Model - Classification of Plant-Based Goods.
     */
    final class VeganCategory extends Model
    {
        protected $table = 'vegan_categories';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'description', 'icon', 'correlation_id'];

        public function products(): HasMany { return $this->hasMany(VeganProduct::class, 'vegan_category_id'); }
    }

    /**
     * VeganSubscriptionBox Model - Weekly/Monthly curated plant-based items.
     */
    final class VeganSubscriptionBox extends Model
    {
        protected $table = 'vegan_subscription_boxes';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'description', 'price_monthly', 'plan_type', 'included_product_ids', 'is_active', 'correlation_id'];
        protected $casts = ['included_product_ids' => 'json', 'price_monthly' => 'integer', 'is_active' => 'boolean'];

        public function reviews(): MorphMany { return $this->morphMany(VeganReview::class, 'reviewable'); }
    }

    /**
     * VeganRecipe Model - Educational content to boost product sales.
     */
    final class VeganRecipe extends Model
    {
        protected $table = 'vegan_recipes';
        protected $fillable = ['uuid', 'tenant_id', 'title', 'description', 'steps', 'cooking_time_minutes', 'difficulty', 'ingredient_ids', 'nutrition_total', 'correlation_id'];
        protected $casts = ['steps' => 'json', 'ingredient_ids' => 'json', 'nutrition_total' => 'json'];
    }

    /**
     * VeganReview Model - Verification of quality and taste.
     */
    final class VeganReview extends Model
    {
        protected $table = 'vegan_reviews';
        protected $fillable = ['uuid', 'tenant_id', 'user_id', 'reviewable_type', 'reviewable_id', 'rating', 'comment', 'meta', 'correlation_id'];
        protected $casts = ['meta' => 'json', 'rating' => 'integer'];

        public function reviewable(): \Illuminate\Database\Eloquent\Relations\MorphTo { return $this->morphTo(); }
    }
