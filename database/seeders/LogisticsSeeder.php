<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Logistics\Models\LogisticsRoute;
use App\Domains\Logistics\Models\LogisticsShipment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Logistics vertical...');

            for ($i = 1; $i <= 25; $i++) {
                LogisticsRoute::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Route {$i}",
                    'origin' => "Origin {$i}",
                    'destination' => "Destination {$i}",
                    'distance_km' => rand(100, 5000),
                    'estimated_hours' => rand(2, 48),
                    'status' => 'active',
                ]);

                LogisticsShipment::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'tracking_number' => "TRACK-{$i}",
                    'route_id' => $i,
                    'weight_kg' => rand(10, 1000),
                    'price' => rand(5000, 100000),
                    'status' => ['pending', 'in_transit', 'delivered'][rand(0, 2)],
                    'sender_id' => rand(1, 10),
                    'receiver_id' => rand(1, 10),
                ]);
            }

            $this->command->info('Logistics vertical seeded successfully.');
        });
    }
}
