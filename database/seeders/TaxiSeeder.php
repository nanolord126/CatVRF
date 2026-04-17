<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\TaxiDriver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TaxiSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Taxi vertical...');

            for ($i = 1; $i <= 25; $i++) {
                TaxiDriver::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Driver {$i}",
                    'phone' => "+7900{$i}0000",
                    'license_number' => "LIC-{$i}",
                    'vehicle_model' => "Car Model {$i}",
                    'status' => 'available',
                ]);

                TaxiRide::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'driver_id' => $i,
                    'passenger_id' => rand(1, 10),
                    'pickup_address' => "Pickup {$i}",
                    'destination_address' => "Destination {$i}",
                    'distance_km' => rand(5, 50),
                    'price' => rand(500, 5000),
                    'status' => ['pending', 'in_progress', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Taxi vertical seeded successfully.');
        });
    }
}
