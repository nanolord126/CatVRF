<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Apartment extends Model
{
    use HasFactory;

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
