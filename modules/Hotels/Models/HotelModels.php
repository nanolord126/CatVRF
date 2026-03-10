<?php

namespace Modules\Hotels\Models;

use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth, HasEcosystemMedia};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Hotel extends Model implements HasMedia
{
    use HasEcosystemFeatures, HasEcosystemAuth, HasEcosystemMedia, InteractsWithMedia;

    protected $fillable = [
        'name', 'stars', 'category', 'description', 'address', 'latitude', 'longitude', 
        'amenities', 'policy', 'is_active', 'metadata',
        'has_fishing', 'has_zoo', 'has_forest_access',
        'has_gym', 'has_pool', 'has_spa', 'has_restaurant', 'has_shop', 'has_flowers_shop',
        'distance_to_sea', 'distance_to_center', 'distance_to_pharmacy', 
        'distance_to_hospital', 'distance_to_landmark', 'distance_to_church'
    ];

    protected $casts = [
        'amenities' => 'array',
        'policy' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'stars' => 'integer',
        'has_fishing' => 'boolean',
        'has_zoo' => 'boolean',
        'has_forest_access' => 'boolean',
        'has_gym' => 'boolean',
        'has_pool' => 'boolean',
        'has_spa' => 'boolean',
        'has_restaurant' => 'boolean',
        'has_shop' => 'boolean',
        'has_flowers_shop' => 'boolean',
        'distance_to_sea' => 'float',      // в метрах
        'distance_to_center' => 'float',   // в метрах
        'distance_to_pharmacy' => 'float', // в метрах
        'distance_to_hospital' => 'float', // в метрах
        'distance_to_landmark' => 'float', // в метрах
        'distance_to_church' => 'float',   // в метрах
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}

class Room extends Model implements HasMedia
{
    use HasEcosystemFeatures, HasEcosystemMedia, InteractsWithMedia;

    protected $fillable = [
        'hotel_id', 'number', 'name', 'room_type', 'price', 'capacity', 
        'square_meters', 'amenities', 'status', 'is_clean', 'metadata',
        'has_balcony', 'has_kitchen', 'has_air_con', 'has_wifi', 'has_tv'
    ];

    protected $casts = [
        'amenities' => 'array', // детальный JSON (фен, тапочки и т.д.)
        'metadata' => 'array',
        'is_clean' => 'boolean',
        'price' => 'decimal:2',
        'square_meters' => 'decimal:2',
        'has_balcony' => 'boolean',
        'has_kitchen' => 'boolean',
        'has_air_con' => 'boolean',
        'has_wifi' => 'boolean',
        'has_tv' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
