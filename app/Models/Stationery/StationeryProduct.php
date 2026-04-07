<?php declare(strict_types=1);

namespace App\Models\Stationery;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class StationeryProduct extends Model
{
    use HasFactory;

    protected $table = 'stationery_products';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'store_id',
            'category_id',
            'name',
            'sku',
            'price_cents',
            'b2b_price_cents',
            'stock_quantity',
            'min_stock_threshold',
            'attributes',
            'is_active',
            'has_gift_wrapping',
            'gift_wrap_price_cents',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'attributes' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'has_gift_wrapping' => 'boolean',
            'price_cents' => 'integer',
            'b2b_price_cents' => 'integer',
            'gift_wrap_price_cents' => 'integer',
            'stock_quantity' => 'integer',
            'min_stock_threshold' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                if ($this->guard->check() && empty($model->tenant_id)) {
                    $model->tenant_id = $this->guard->user()->tenant_id;
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if ($this->guard->check()) {
                    $builder->where('tenant_id', $this->guard->user()->tenant_id);
                }
            });
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(StationeryStore::class, 'store_id');
        }

        public function category(): BelongsTo
        {
            return $this->belongsTo(StationeryCategory::class, 'category_id');
        }

        public function reviews(): MorphMany
        {
            return $this->morphMany(StationeryReview::class, 'reviewable');
        }

        /**
         * Get effective price based on buyer type (B2B/B2C).
         */
        public function getEffectivePrice(bool $isB2B = false): int
        {
            if ($isB2B && $this->b2b_price_cents > 0) {
                return $this->b2b_price_cents;
            }

            return $this->price_cents;
        }
}
