<?php declare(strict_types=1);

namespace App\Domains\Travel\TravelTourism\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class B2BTravelStorefront
 *
 * Part of the Travel vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\Travel\TravelTourism\Models
 */
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
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', $this->guard->user()?->tenant_id ?? null));
    }

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BTravelOrder::class, 'b2b_travel_storefront_id');
    }
}
