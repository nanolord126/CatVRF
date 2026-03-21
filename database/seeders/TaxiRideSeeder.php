<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Auto\Models\TaxiRide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Тестовые поездки такси (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class TaxiRideSeeder extends Seeder
{
    public function run(): void
    {
        $rides = [
            ['vehicle_class' => 'economy', 'distance_km' => 5.5, 'fare_amount' => 150, 'status' => 'completed'],
            ['vehicle_class' => 'comfort', 'distance_km' => 8.2, 'fare_amount' => 240, 'status' => 'completed'],
            ['vehicle_class' => 'premium', 'distance_km' => 12.1, 'fare_amount' => 420, 'status' => 'completed'],
        ];

        foreach ($rides as $ride) {
            TaxiRide::factory()->create(array_merge($ride, ['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]));
        }
    }
}
