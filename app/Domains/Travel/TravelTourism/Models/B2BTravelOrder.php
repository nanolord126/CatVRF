<?php

declare(strict_types=1);

namespace App\Domains\Travel\TravelTourism\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Class B2BTravelOrder
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class B2BTravelOrder extends Model
{
    protected $table = 'b2b_travel_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'b2b_travel_storefront_id',
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

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BTravelStorefront::class, 'b2b_travel_storefront_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', $this->guard->user()?->tenant_id ?? null));
    }
}
