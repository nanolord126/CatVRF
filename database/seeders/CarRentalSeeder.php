<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\CarRental\Models\CarRentalBooking;
use App\Domains\CarRental\Models\CarRentalVehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CarRentalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Car Rental vertical...');

            for ($i = 1; $i <= 20; $i++) {
                CarRentalVehicle::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'make' => "Make {$i}",
                    'model' => "Model {$i}",
                    'year' => rand(2020, 2024),
                    'category' => ['economy', 'comfort', 'premium'][rand(0, 2)],
                    'price_per_day' => rand(2000, 15000),
                    'status' => 'available',
                ]);

                CarRentalBooking::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'vehicle_id' => $i,
                    'customer_id' => rand(1, 10),
                    'pickup_date' => now()->addDays(rand(1, 30)),
                    'return_date' => now()->addDays(rand(31, 60)),
                    'total_price' => rand(10000, 100000),
                    'status' => ['confirmed', 'pending', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Car Rental vertical seeded successfully.');
        });
    }
}
