<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\HomeServices\Models\HomeServicesOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HomeServicesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Home Services vertical...');

            for ($i = 1; $i <= 25; $i++) {
                HomeServicesOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'service_type' => ['cleaning', 'repair', 'installation'][rand(0, 2)],
                    'customer_id' => rand(1, 10),
                    'address' => "Address {$i}",
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'price' => rand(2000, 50000),
                    'status' => ['pending', 'in_progress', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Home Services vertical seeded successfully.');
        });
    }
}
