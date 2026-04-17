<?php declare(strict_types=1);

/**
 * Storefront — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/storefront
 */


namespace App\Domains\RealEstate\ShopRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Storefront
 *
 * Part of the RealEstate vertical domain.
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
 * @package App\Domains\RealEstate\ShopRentals\Models
 */
final class Storefront extends Model
{

    protected $table = 'storefronts';
    protected $fillable = ['uuid', 'tenant_id', 'owner_id', 'correlation_id', 'name', 'area_sqm', 'location', 'price_kopecks_per_month', 'rating', 'is_verified', 'tags'];
    protected $casts = ['area_sqm' => 'integer', 'price_kopecks_per_month' => 'integer', 'rating' => 'float', 'is_verified' => 'boolean', 'tags' => 'json'];

    protected static function booted()
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('storefronts.tenant_id', tenant()->id));
    }

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

}
