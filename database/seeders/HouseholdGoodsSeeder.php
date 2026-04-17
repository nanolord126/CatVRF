<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\HouseholdGoods\Models\HouseholdGoodsProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HouseholdGoodsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Household Goods vertical...');

            for ($i = 1; $i <= 30; $i++) {
                HouseholdGoodsProduct::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Product {$i}",
                    'description' => "Description for product {$i}",
                    'category' => ['kitchen', 'bathroom', 'bedroom'][rand(0, 2)],
                    'price' => rand(500, 20000),
                    'stock' => rand(10, 100),
                    'status' => 'available',
                ]);
            }

            $this->command->info('Household Goods vertical seeded successfully.');
        });
    }
}
