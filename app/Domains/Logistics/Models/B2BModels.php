<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BLogisticsStorefront extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

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

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', auth()->user()?->tenant_id ?? null));
        }

        public function b2bOrders(): HasMany
        {
            return $this->hasMany(B2BLogisticsOrder::class, 'b2b_logistics_storefront_id');
        }
    }

    namespace App\Domains\Logistics\Models;

    final class B2BLogisticsOrder extends Model
    {
        use SoftDeletes;

        protected $table = 'b2b_logistics_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'b2b_logistics_storefront_id',
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
            return $this->belongsTo(B2BLogisticsStorefront::class, 'b2b_logistics_storefront_id');
        }
}
