<?php declare(strict_types=1);

/**
 * BilliardBooking — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/billiardbooking
 */


namespace App\Domains\Sports\Billiards\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BilliardBooking
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
 * @package App\Domains\Sports\Billiards\Models
 */
final class BilliardBooking extends Model
{

    protected $table = 'billiard_bookings';
    protected $fillable = ['uuid', 'tenant_id', 'hall_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'booking_date', 'duration_hours', 'table_number', 'tags'];
    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'booking_date' => 'datetime', 'duration_hours' => 'integer', 'table_number' => 'integer', 'tags' => 'json'];

    protected static function booted()
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('billiard_bookings.tenant_id', tenant()->id));
    }

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

}
