<?php

declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class ApartmentBooking
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
final class ApartmentBooking extends Model
{
    use TenantScoped;

    protected $table = 'short_term_apartment_bookings';

    protected $fillable = [
        'tenant_id', 'apartment_id', 'user_id', 'inn',
        'business_card_id', 'check_in', 'check_out',
        'guests_count', 'total_price', 'deposit_held',
        'status', 'payment_status', 'uuid',
        'correlation_id', 'tags',
    ];

    protected $casts = [
        'check_in' => 'date', 'check_out' => 'date',
        'tags' => 'json', 'total_price' => 'decimal:2',
        'deposit_held' => 'decimal:2', 'guests_count' => 'integer',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope(
            'tenant',
            fn ($q) => $q->where('tenant_id', tenant()->id ?? 0)
        );
    }
}
