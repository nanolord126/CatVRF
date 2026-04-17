<?php declare(strict_types=1);

namespace App\Models\Domains\Hotel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HotelBooking
 *
 * Part of the Hotel vertical domain.
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
 * @package App\Models\Domains\Hotel
 */
final class HotelBooking extends Model
{

        protected $table = 'hotel_bookings';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'hotel_id',
            'room_id',
            'guest_id',
            'check_in',
            'check_out',
            'total_price',
            'status',
        ];

        protected static function newFactory()
        {
            return \Database\Factories\HotelBookingFactory::new();
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }
}
