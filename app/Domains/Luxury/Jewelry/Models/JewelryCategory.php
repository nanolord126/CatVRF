<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;

use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JewelryDomainTrait, SoftDeletes;
use JewelryDomainTrait;

/**
     * JewelryCategory (Layer 1/9)
     */
final class JewelryCategory extends Model
{
        use JewelryDomainTrait;

        protected $table = 'jewelry_categories';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'sort_order', 'correlation_id'];

        public function products(): HasMany
        {
            return $this->hasMany(JewelryProduct::class, 'category_id');
        }
    }
