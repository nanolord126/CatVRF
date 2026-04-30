<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class B2BLogisticsStorefront extends Model
{
    use TenantScoped;

    protected $table = 'b2b_logistics_storefronts';

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

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BLogisticsOrder::class, 'b2b_logistics_storefront_id');
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', (function_exists('tenant') && tenant()) ? tenant()->id : null));
    }
}
