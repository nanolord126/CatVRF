<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Flowers\Models\FlowersBouquet;
use App\Domains\Flowers\Models\FlowersOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FlowersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Flowers vertical...');

            for ($i = 1; $i <= 25; $i++) {
                FlowersBouquet::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Bouquet {$i}",
                    'description' => "Description for bouquet {$i}",
                    'flowers' => ['roses', 'tulips', 'lilies'][rand(0, 2)],
                    'price' => rand(1000, 15000),
                    'stock' => rand(10, 50),
                    'status' => 'available',
                ]);

                FlowersOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'customer_id' => rand(1, 10),
                    'bouquet_id' => $i,
                    'quantity' => rand(1, 10),
                    'total_price' => rand(2000, 50000),
                    'delivery_date' => now()->addDays(rand(1, 7)),
                    'status' => ['pending', 'delivered', 'cancelled'][rand(0, 2)],
                ]);
            }

            $this->command->info('Flowers vertical seeded successfully.');
        });
    }
}
