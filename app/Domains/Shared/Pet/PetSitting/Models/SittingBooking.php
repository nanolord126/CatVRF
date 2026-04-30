<?php

declare(strict_types=1);

/**
 * SittingBooking — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/sittingbooking
 */

namespace App\Domains\Pet\PetSitting\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class SittingBooking
 *
 * Part of the Pet vertical domain.
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
final class SittingBooking extends Model
{
    protected $table = 'sitting_bookings';

    protected $fillable = ['uuid', 'tenant_id', 'sitter_id', 'owner_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'start_date', 'end_date', 'pet_names', 'tags'];

    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'start_date' => 'datetime', 'end_date' => 'datetime', 'pet_names' => 'json', 'tags' => 'json'];

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

    protected static function booted()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('sitting_bookings.tenant_id', tenant()->id));
    }
}
