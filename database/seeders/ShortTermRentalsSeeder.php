<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\ShortTermRentals\Models\ShortTermRentalsProperty;
use App\Domains\ShortTermRentals\Models\ShortTermRentalsBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ShortTermRentalsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Short Term Rentals vertical...');

            for ($i = 1; $i <= 20; $i++) {
                ShortTermRentalsProperty::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'title' => "Property {$i}",
                    'description' => "Description for property {$i}",
                    'type' => ['apartment', 'house', 'studio'][rand(0, 2)],
                    'address' => "Address {$i}",
                    'price_per_night' => rand(3000, 30000),
                    'capacity' => rand(1, 10),
                    'status' => 'available',
                ]);

                ShortTermRentalsBooking::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'property_id' => $i,
                    'guest_id' => rand(1, 10),
                    'check_in' => now()->addDays(rand(1, 30)),
                    'check_out' => now()->addDays(rand(31, 45)),
                    'total_price' => rand(15000, 300000),
                    'status' => ['confirmed', 'pending', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Short Term Rentals vertical seeded successfully.');
        });
    }
}
