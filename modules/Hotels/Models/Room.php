<?php

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'number', 'name', 'price', 'status', 'requires_housekeeping',
        'is_clean', 'last_cleaned_at', 'needs_laundry',
        'room_type', 'square_meters', 'amenities', 'photos', 'star_rating'
    ];

    protected $casts = [
        'amenities' => 'array',
        'photos' => 'array',
        'is_clean' => 'boolean',
        'requires_housekeeping' => 'boolean',
        'needs_laundry' => 'boolean',
        'last_cleaned_at' => 'datetime',
        'square_meters' => 'decimal:2',
    ];
}

class Booking extends Model
{
    protected $table = 'hotel_bookings';
    protected $fillable = ['room_id', 'check_in', 'check_out', 'total_price', 'status'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
