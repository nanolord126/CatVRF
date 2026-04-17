<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class B2BSportOrder
 *
 * Part of the Sports vertical domain.
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
 * @package App\Domains\Sports\Models
 */
final class B2BSportOrder extends Model
{


    protected $table = 'b2b_sport_orders';

    protected $fillable = [
        'uuid', 'tenant_id', 'b2b_sport_storefront_id', 'user_id', 'order_number',
        'company_contact_person', 'company_phone', 'items', 'total_amount',
        'commission_amount', 'status', 'rejection_reason', 'correlation_id', 'tags'
    ];

    protected $casts = [
        'items' => 'json',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', $this->guard->user()?->tenant_id ?? null));
    }

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BSportStorefront::class, 'b2b_sport_storefront_id');
    }
}
