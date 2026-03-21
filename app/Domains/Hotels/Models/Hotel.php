<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class Hotel extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'geo_point',
        'star_rating',
        'total_rooms',
        'description',
        'amenities',
        'status',
        'rating',
        'is_verified',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'amenities' => 'collection',
        'tags' => 'collection',
        'metadata' => 'json',
        'rating' => 'float',
        'is_verified' => 'boolean',
    ];

    public function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(HotelImage::class);
    }

    public function payoutSchedule()
    {
        return $this->hasOne(PayoutSchedule::class);
    }
}
