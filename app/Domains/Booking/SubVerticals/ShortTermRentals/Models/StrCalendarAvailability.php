<?php

declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class StrCalendarAvailability
 *
 * Part of the ShortTermRentals vertical domain.
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
final class StrCalendarAvailability extends Model
{
    use TenantScoped;

    protected $table = 'str_calendar_availability';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'apartment_id',
        'date',
        'is_available',
        'price_override_b2c',
        'price_override_b2b',
        'reason',
        'correlation_id',
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
        'price_override_b2c' => 'integer',
        'price_override_b2b' => 'integer',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(StrApartment::class, 'apartment_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
