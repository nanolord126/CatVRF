<?php declare(strict_types=1);

namespace App\Domains\Auto\CarWashing\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CarWashStation
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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\Auto\CarWashing\Models
 */
final class CarWashStation extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'car_wash_stations';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'owner_id',
        'correlation_id',
        'name',
        'address',
        'price_kopecks_per_service',
        'service_type',
        'rating',
        'tags',
    ];

    protected $casts = [
        'price_kopecks_per_service' => 'integer',
        'rating' => 'float',
        'tags' => 'json',
    ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('car_wash_stations.tenant_id', tenant()->id));
        }
}
