<?php

declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class B2BFitnessStorefront extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_fitness_storefronts';

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
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', auth()->user()?->tenant_id ?? null));
    }

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BFitnessOrder::class, 'b2b_fitness_storefront_id');
    }
}

namespace App\Domains\Fitness\Models;

final class B2BFitnessOrder extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_fitness_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'b2b_fitness_storefront_id',
        'order_number',
        'company_contact_person',
        'company_phone',
        'items_json',
        'total_amount',
        'commission_amount',
        'discount_amount',
        'status',
        'expected_delivery_at',
        'notes',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'items_json' => 'json',
        'tags' => 'json',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'expected_delivery_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', auth()->user()?->tenant_id ?? null));
    }

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BFitnessStorefront::class, 'b2b_fitness_storefront_id');
    }
}
