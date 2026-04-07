<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



use Illuminate\Database\Eloquent\SoftDeletes;
/**
     * JewelryStore (Layer 1/9)
     */
final class JewelryStore extends Model
{
        use JewelryDomainTrait, SoftDeletes;

        protected $table = 'jewelry_stores';
        protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'name', 'license_number', 'settings', 'tags', 'correlation_id'];
        protected $casts = [
            'settings' => 'array',
            'tags' => 'array',
            'deleted_at' => 'datetime',
        ];

        public function products(): HasMany
        {
            return $this->hasMany(JewelryProduct::class, 'store_id');
        }

        public function collections(): HasMany
        {
            return $this->hasMany(JewelryCollection::class, 'store_id');
        }

        public function customOrders(): HasMany
        {
            return $this->hasMany(JewelryCustomOrder::class, 'store_id');
        }
    }
