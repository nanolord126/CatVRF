<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\SoftDeletes;
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
