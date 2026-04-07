<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
     * FurnitureCategory Model
     */
final class FurnitureCategory extends Model
{
        use FurnitureDomainTrait;

        protected $table = 'furniture_categories';

        protected $fillable = [
            'uuid', 'tenant_id', 'name', 'slug', 'description',
            'sort_order', 'correlation_id'
        ];

        public function products(): HasMany
        {
            return $this->hasMany(FurnitureProduct::class);
        }
    }
