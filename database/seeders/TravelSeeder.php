<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Travel\Models\TravelDestination;
use App\Domains\Travel\Models\TravelBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TravelSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Travel vertical...');

            for ($i = 1; $i <= 20; $i++) {
                TravelDestination::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Destination {$i}",
                    'description' => "Description for destination {$i}",
                    'country' => "Country {$i}",
                    'city' => "City {$i}",
                    'price_per_day' => rand(5000, 50000),
                    'available_rooms' => rand(5, 50),
                    'rating' => rand(30, 50) / 10,
                    'status' => 'available',
                ]);

                TravelBooking::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'destination_id' => $i,
                    'user_id' => rand(1, 10),
                    'check_in' => now()->addDays(rand(1, 30)),
                    'check_out' => now()->addDays(rand(31, 60)),
                    'guests' => rand(1, 5),
                    'total_price' => rand(50000, 500000),
                    'status' => ['confirmed', 'pending', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Travel vertical seeded successfully.');
        });
    }
}
