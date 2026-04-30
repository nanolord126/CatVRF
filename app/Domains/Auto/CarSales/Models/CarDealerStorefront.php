<?php

declare(strict_types=1);

namespace App\Domains\Auto\CarSales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Class CarDealerStorefront
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
final class CarDealerStorefront extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'b2b_auto_storefronts';

    protected $fillable = [
        'uuid', 'tenant_id', 'company_name', 'inn', 'description',
        'auto_brands', 'wholesale_discount', 'min_order_amount', 'is_verified',
        'is_active', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'auto_brands' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'wholesale_discount' => 'decimal:2',
    ];

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BAutoOrder::class, 'b2b_auto_storefront_id');
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
