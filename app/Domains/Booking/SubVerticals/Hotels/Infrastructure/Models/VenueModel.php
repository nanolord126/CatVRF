<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class VenueModel extends BaseDomainModel
{
    use HasFactory, SoftDeletes;
    use HasMediaTrait;

    protected $table = 'hotels_venues';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'star_rating',
        'property_type',
        'total_rooms',
        'total_floors',
        'amenities',
        'checkin_policy',
        'checkout_policy',
        'cancellation_policy',
        'is_active',
        'is_chain',
        'parent_venue_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'star_rating' => 'integer',
        'total_rooms' => 'integer',
        'total_floors' => 'integer',
        'amenities' => 'array',
        'checkin_policy' => 'array',
        'checkout_policy' => 'array',
        'cancellation_policy' => 'array',
        'is_active' => 'boolean',
        'is_chain' => 'boolean',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(RoomModel::class, 'venue_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingModel::class, 'venue_id');
    }
}
