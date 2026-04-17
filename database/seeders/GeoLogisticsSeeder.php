<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\GeoLogistics\Models\GeoLogisticsRoute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GeoLogisticsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Geo Logistics vertical...');

            for ($i = 1; $i <= 25; $i++) {
                GeoLogisticsRoute::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Route {$i}",
                    'origin_id' => rand(1, 30),
                    'destination_id' => rand(1, 30),
                    'distance_km' => rand(10, 500),
                    'estimated_minutes' => rand(30, 480),
                    'status' => 'active',
                ]);
            }

            $this->command->info('Geo Logistics vertical seeded successfully.');
        });
    }
}
