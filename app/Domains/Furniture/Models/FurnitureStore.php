<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
/**
     * FurnitureStore Model
     */
final class FurnitureStore extends Model
{
        use FurnitureDomainTrait, SoftDeletes;

        protected $table = 'furniture_stores';

        protected $fillable = [
            'uuid', 'tenant_id', 'name', 'slug', 'address',
            'schedule_json', 'rating', 'is_verified',
            'correlation_id', 'tags'
        ];

        protected $casts = [
            'schedule_json' => 'json',
            'is_verified' => 'boolean',
            'tags' => 'json',
            'rating' => 'float',
        ];

        public function products(): HasMany
        {
            return $this->hasMany(FurnitureProduct::class);
        }
    }
