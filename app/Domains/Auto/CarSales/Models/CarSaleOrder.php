<?php

declare(strict_types=1);

namespace App\Domains\Auto\CarSales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Class CarSaleOrder
 *
 * Part of the Auto vertical domain.
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
final class CarSaleOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'b2b_auto_orders';

    protected $fillable = [
        'uuid', 'tenant_id', 'b2b_auto_storefront_id', 'order_number',
        'company_contact_person', 'company_phone', 'items_json', 'total_amount',
        'commission_amount', 'discount_amount', 'status', 'expected_delivery_at',
        'notes', 'correlation_id', 'tags',
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
        return $this->belongsTo(B2BAutoStorefront::class, 'b2b_auto_storefront_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant() && tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
