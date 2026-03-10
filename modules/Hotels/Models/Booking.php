<?php

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'hotel_bookings';
    protected $fillable = ['room_id', 'check_in', 'check_out', 'total_price', 'status'];

    public function calculateCommission(): float
    {
        $basePercent = 0.10; // 10% standard commission
        
        // Use Stancl Tenancy global helper or tenant() to check commission uplift
        if (tenant('commission_uplift') || !tenant('inn')) {
             $basePercent += 0.20; // +20% Agency Premium
        }

        return round($this->total_price * $basePercent, 2);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
