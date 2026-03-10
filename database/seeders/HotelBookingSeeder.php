<?php

namespace Database\Seeders;

use App\Models\Domains\Hotel\HotelBooking;
use Illuminate\Database\Seeder;

class HotelBookingSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = [
            ['total_price' => 2500, 'status' => 'confirmed'],
            ['total_price' => 3200, 'status' => 'checked_in'],
            ['total_price' => 1800, 'status' => 'pending'],
        ];

        foreach ($bookings as $booking) {
            HotelBooking::factory()->create($booking);
        }
    }
}
