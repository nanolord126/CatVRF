<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\HotelRoom;
use App\Domains\Hotels\Models\HotelBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HotelsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Hotels vertical...');

            for ($i = 1; $i <= 15; $i++) {
                Hotel::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Hotel {$i}",
                    'description' => "Description for hotel {$i}",
                    'address' => "Address {$i}",
                    'city' => "City {$i}",
                    'stars' => rand(3, 5),
                    'rating' => rand(35, 50) / 10,
                    'status' => 'active',
                ]);

                HotelRoom::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'hotel_id' => $i,
                    'room_number' => $i * 100,
                    'room_type' => ['standard', 'deluxe', 'suite'][rand(0, 2)],
                    'price_per_night' => rand(5000, 50000),
                    'capacity' => rand(1, 4),
                    'status' => 'available',
                ]);

                HotelBooking::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'hotel_id' => $i,
                    'room_id' => $i,
                    'user_id' => rand(1, 10),
                    'check_in' => now()->addDays(rand(1, 30)),
                    'check_out' => now()->addDays(rand(31, 45)),
                    'total_price' => rand(20000, 200000),
                    'status' => ['confirmed', 'pending', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Hotels vertical seeded successfully.');
        });
    }
}
