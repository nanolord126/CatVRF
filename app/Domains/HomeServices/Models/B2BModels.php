<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BHomeServiceStorefront extends Model
{

    use HasFactory;

    use SoftDeletes;

        protected $table = 'b2b_home_service_storefronts';

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

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', (function_exists('tenant') && tenant()) ? tenant()->id : null));
        }

        public function b2bOrders(): HasMany
        {
            return $this->hasMany(B2BHomeServiceOrder::class, 'b2b_home_service_storefront_id');
        }
    }

    namespace App\Domains\HomeServices\Models;

    final class B2BHomeServiceOrder extends Model
    {
        use SoftDeletes;

        protected $table = 'b2b_home_service_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'b2b_home_service_storefront_id',
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

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', (function_exists('tenant') && tenant()) ? tenant()->id : null));
        }

        public function storefront(): BelongsTo
        {
            return $this->belongsTo(B2BHomeServiceStorefront::class, 'b2b_home_service_storefront_id');
        }
}
