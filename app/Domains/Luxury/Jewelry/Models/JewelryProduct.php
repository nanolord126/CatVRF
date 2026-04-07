<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



use Illuminate\Database\Eloquent\SoftDeletes;
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
