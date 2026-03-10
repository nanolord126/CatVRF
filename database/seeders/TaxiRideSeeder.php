<?php

namespace Database\Seeders;

use App\Models\Domains\Taxi\TaxiRide;
use Illuminate\Database\Seeder;

class TaxiRideSeeder extends Seeder
{
    public function run(): void
    {
        $rides = [
            ['vehicle_class' => 'economy', 'distance_km' => 5.5, 'fare_amount' => 150],
            ['vehicle_class' => 'comfort', 'distance_km' => 8.2, 'fare_amount' => 240],
            ['vehicle_class' => 'premium', 'distance_km' => 12.1, 'fare_amount' => 420],
        ];

        foreach ($rides as $ride) {
            TaxiRide::factory()->create($ride);
        }
    }
}
