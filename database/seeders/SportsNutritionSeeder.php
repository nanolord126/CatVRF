<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\SportsNutrition\Models\SportsNutritionProduct;
use App\Domains\SportsNutrition\Models\SportsNutritionOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SportsNutritionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Sports Nutrition vertical...');

            for ($i = 1; $i <= 25; $i++) {
                SportsNutritionProduct::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Product {$i}",
                    'description' => "Description for product {$i}",
                    'category' => ['protein', 'creatine', 'vitamins'][rand(0, 2)],
                    'price' => rand(500, 15000),
                    'stock' => rand(20, 200),
                    'status' => 'available',
                ]);

                SportsNutritionOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'customer_id' => rand(1, 10),
                    'items' => rand(1, 5),
                    'total_price' => rand(1000, 30000),
                    'status' => ['pending', 'shipped', 'delivered'][rand(0, 2)],
                ]);
            }

            $this->command->info('Sports Nutrition vertical seeded successfully.');
        });
    }
}
