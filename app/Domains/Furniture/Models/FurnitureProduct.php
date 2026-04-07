<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;

use FurnitureDomainTrait, SoftDeletes;
use FurnitureDomainTrait;
use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
