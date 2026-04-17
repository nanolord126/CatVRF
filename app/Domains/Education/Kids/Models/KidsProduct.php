<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsProduct extends Model
{

    use HasFactory, SoftDeletes;

        protected $table = 'kids_products';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'store_id',
            'name',
            'description',
            'price',
            'stock_quantity',
            'sku',
            'barcode',
            'age_range',
            'safety_class',
            'material_details',
            'origin_country',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'price' => 'integer', // Kopecks (Canon 2026)
            'age_range' => 'json', // min_months, max_months
            'material_details' => 'json',
            'tags' => 'json',
        ];

        /**
         * Boot the model with tenant and correlation scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (string) (tenant()->id ?? 'system');
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Store relationship.
         */
        public function store(): BelongsTo
        {
            return $this->belongsTo(KidsStore::class, 'store_id');
        }

        /**
         * Specialized toy metadata.
         */
        public function toy(): HasOne
        {
            return $this->hasOne(KidsToy::class, 'product_id');
        }

        /**
         * Specialized clothing metadata.
         */
        public function clothing(): HasOne
        {
            return $this->hasOne(KidsClothing::class, 'product_id');
        }

        /**
         * Safety filter.
         */
        public function scopeSafeForInfants(Builder $query): Builder
        {
            return $query->where('safety_class', 'A')->whereRaw("CAST(age_range->>'min_months' AS INTEGER) <= 3");
        }

        /**
         * Budget filter.
         */
        public function scopeUnderBudget(Builder $query, int $limit): Builder
        {
            return $query->where('price', '<=', $limit);
        }

        /**
         * In-stock filter.
         */
        public function scopeAvailable(Builder $query): Builder
        {
            return $query->where('stock_quantity', '>', 0);
        }

        /**
         * Formatted price display helper.
         */
        public function getFormattedPriceAttribute(): string
        {
            return number_format($this->price / 100, 2, '.', ' ') . ' RUB';
        }
}
