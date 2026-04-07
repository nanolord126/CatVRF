<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



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
