<?php

declare(strict_types=1);

namespace App\Models\Domains\Hotel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelBooking extends Model
{
    use HasFactory;

    protected $table = 'hotel_bookings';

    protected $fillable = [
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
