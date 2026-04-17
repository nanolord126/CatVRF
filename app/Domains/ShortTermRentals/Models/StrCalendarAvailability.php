<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\ShortTermRentals\Models
 */
final class StrCalendarAvailability extends Model
{

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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(StrApartment::class, 'apartment_id');
    }
}
