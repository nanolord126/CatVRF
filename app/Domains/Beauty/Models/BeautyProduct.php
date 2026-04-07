<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BeautyProduct extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

        protected $table = 'beauty_products';

        protected $fillable = [
        'uuid',
            'tenant_id',
            'salon_id',
            'name',
            'sku',
            'current_stock',
            'min_stock_threshold',
            'price',
            'consumable_type',
            'description',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $hidden = [];

        protected $casts = [
            'tags' => 'collection',
            'metadata' => 'json',
            'price' => 'integer',
            'current_stock' => 'integer',
            'min_stock_threshold' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scoping', static function ($query): void {
                if (function_exists('tenant') && tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });

            static::creating(static function (self $model): void {
                if (empty($model->uuid)) {
                    $model->uuid = (string) \Illuminate\Support\Str::uuid();
                }
            });
        }

        public function salon(): BelongsTo
        {
            return $this->belongsTo(BeautySalon::class, 'salon_id');
        }

        /**
         * Check if product is in stock and available for sale.
         */
        public function isInStock(): bool
        {
            return $this->current_stock > 0;
        }

        /**
         * Check if stock is below minimum threshold.
         */
        public function isBelowMinStock(): bool
        {
            return $this->current_stock <= $this->min_stock_threshold;
        }

        /**
         * Get price in rubles (from kopecks).
         */
        public function getPriceRubles(): float
        {
            return $this->price / 100;
        }
}
