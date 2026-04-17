<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BFashionRetailStorefront extends Model
{



        protected $table = 'b2b_fashion_retail_storefronts';

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
            return $this->hasMany('App\Domains\Fashion\FashionRetail\Models\B2BFashionRetailOrder');
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
