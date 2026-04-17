<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Geo\Models\GeoLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GeoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Geo vertical...');

            for ($i = 1; $i <= 30; $i++) {
                GeoLocation::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Location {$i}",
                    'address' => "Address {$i}",
                    'latitude' => rand(55000000, 56000000) / 1000000,
                    'longitude' => rand(37000000, 38000000) / 1000000,
                    'type' => ['pickup', 'delivery', 'warehouse'][rand(0, 2)],
                    'status' => 'active',
                ]);
            }

            $this->command->info('Geo vertical seeded successfully.');
        });
    }
}
