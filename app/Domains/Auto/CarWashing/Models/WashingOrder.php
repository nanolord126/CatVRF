<?php declare(strict_types=1);

namespace App\Domains\Auto\CarWashing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WashingOrder
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
final class WashingOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'washing_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'station_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'booking_date',
        'service_type',
        'tags',
    ];

    protected $casts = [
        'total_kopecks' => 'integer',
        'payout_kopecks' => 'integer',
        'booking_date' => 'datetime',
        'tags' => 'json',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('washing_orders.tenant_id', tenant()->id));
    }
}
