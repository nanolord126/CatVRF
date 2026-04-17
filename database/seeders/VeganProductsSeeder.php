<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\VeganProducts\Models\VeganProductsItem;
use App\Domains\VeganProducts\Models\VeganProductsOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class VeganProductsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Vegan Products vertical...');

            for ($i = 1; $i <= 25; $i++) {
                VeganProductsItem::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Product {$i}",
                    'description' => "Description for product {$i}",
                    'category' => ['food', 'cosmetics', 'clothing'][rand(0, 2)],
                    'price' => rand(200, 5000),
                    'stock' => rand(20, 200),
                    'status' => 'available',
                ]);

                VeganProductsOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'customer_id' => rand(1, 10),
                    'items' => rand(1, 10),
                    'total_price' => rand(500, 30000),
                    'status' => ['pending', 'shipped', 'delivered'][rand(0, 2)],
                ]);
            }

            $this->command->info('Vegan Products vertical seeded successfully.');
        });
    }
}
