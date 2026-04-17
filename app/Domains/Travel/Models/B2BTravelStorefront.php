<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class B2BTravelStorefront extends Model
{



        protected $table = 'b2b_travel_storefronts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'company_name',
            'inn',
            'description',
            'service_categories',
            'wholesale_discount',
            'min_order_amount',
            'is_verified',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'service_categories' => 'json',
            'tags' => 'json',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'wholesale_discount' => 'decimal:2',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant() && tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function b2bOrders(): HasMany
        {
            return $this->hasMany(B2BTravelOrder::class, 'b2b_travel_storefront_id');
        }
}
