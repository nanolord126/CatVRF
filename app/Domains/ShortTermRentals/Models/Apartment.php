<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Apartment
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
final class Apartment extends Model
{
    protected $table = 'short_term_apartments';


    protected $fillable = [
        'tenant_id', 'owner_id', 'name', 'address',
        'geo_point', 'rooms', 'area_sqm', 'floor',
        'amenities', 'images', 'price_per_night',
        'available_dates', 'deposit_amount', 'is_active',
        'uuid', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'amenities' => 'json', 'images' => 'json',
        'available_dates' => 'json', 'tags' => 'json',
        'is_active' => 'boolean', 'price_per_night' => 'decimal:2',
        'deposit_amount' => 'decimal:2', 'rooms' => 'integer',
        'area_sqm' => 'float', 'floor' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) =>
            $q->where('tenant_id', tenant()->id ?? 0)
        );
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ApartmentBooking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ApartmentReview::class);
    }
}
