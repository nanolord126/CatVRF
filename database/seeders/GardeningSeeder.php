<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Gardening\Models\GardeningPlant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GardeningSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Gardening vertical...');

            for ($i = 1; $i <= 30; $i++) {
                GardeningPlant::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Plant {$i}",
                    'description' => "Description for plant {$i}",
                    'category' => ['indoor', 'outdoor', 'succulent'][rand(0, 2)],
                    'price' => rand(200, 10000),
                    'stock' => rand(5, 100),
                    'status' => 'available',
                ]);
            }

            $this->command->info('Gardening vertical seeded successfully.');
        });
    }
}
