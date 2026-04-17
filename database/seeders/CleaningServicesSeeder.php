<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\CleaningServices\Models\CleaningServicesOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CleaningServicesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Cleaning Services vertical...');

            for ($i = 1; $i <= 25; $i++) {
                CleaningServicesOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'service_type' => ['regular', 'deep_clean', 'move_out'][rand(0, 2)],
                    'customer_id' => rand(1, 10),
                    'address' => "Address {$i}",
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'price' => rand(2000, 30000),
                    'status' => ['pending', 'in_progress', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Cleaning Services vertical seeded successfully.');
        });
    }
}
