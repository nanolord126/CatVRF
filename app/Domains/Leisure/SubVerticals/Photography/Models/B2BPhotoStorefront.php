<?php

declare(strict_types=1);

namespace App\Domains\Photography\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class B2BPhotoStorefront
 *
 * Part of the Photography vertical domain.
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
final class B2BPhotoStorefront extends Model
{
    use TenantScoped;

    protected $table = 'b2b_photo_storefronts';

    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id', 'company_name', 'inn', 'description',
        'corporate_packages', 'corporate_rate', 'min_booking_hours', 'is_verified',
        'is_active', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'corporate_packages' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'corporate_rate' => 'decimal:2',
    ];

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BPhotoOrder::class, 'b2b_photo_storefront_id');
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
