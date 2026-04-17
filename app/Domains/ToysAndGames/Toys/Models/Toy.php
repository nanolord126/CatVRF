<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


/**
     * Toy Model (L1)
     * Core business entity for physical goods.
     */
final class Toy extends Model
{
        use ToysDomainTrait, TenantScoped;
        protected $table = 'toys';
        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'category_id', 'age_group_id',
            'title', 'sku', 'description', 'price_b2c', 'price_b2b',
            'stock_quantity', 'safety_certification', 'material_type',
            'is_gift_wrappable', 'is_active', 'metadata', 'tags', 'correlation_id'
        ];
        protected $casts = [
            'metadata' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'is_gift_wrappable' => 'boolean'
        ];

        public function store(): BelongsTo { return $this->belongsTo(ToyStore::class, 'store_id'); }
        public function category(): BelongsTo { return $this->belongsTo(ToyCategory::class, 'category_id'); }
        public function ageGroup(): BelongsTo { return $this->belongsTo(AgeGroup::class, 'age_group_id'); }
        public function reviews(): HasMany { return $this->hasMany(ToyReview::class, 'toy_id'); }
    }
