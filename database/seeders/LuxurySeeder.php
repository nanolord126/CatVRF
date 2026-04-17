<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Luxury\Models\LuxuryProduct;
use App\Domains\Luxury\Models\LuxuryExperience;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LuxurySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Luxury vertical...');

            for ($i = 1; $i <= 20; $i++) {
                LuxuryProduct::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Luxury Product {$i}",
                    'description' => "Description for luxury product {$i}",
                    'price' => rand(100000, 10000000),
                    'brand' => "Luxury Brand {$i}",
                    'category' => ['watches', 'jewelry', 'bags', 'cars'][rand(0, 3)],
                    'stock' => rand(1, 10),
                    'status' => 'available',
                ]);

                LuxuryExperience::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Experience {$i}",
                    'description' => "Description for experience {$i}",
                    'price' => rand(50000, 5000000),
                    'duration_hours' => rand(2, 48),
                    'location' => "Location {$i}",
                    'status' => 'available',
                ]);
            }

            $this->command->info('Luxury vertical seeded successfully.');
        });
    }
}
