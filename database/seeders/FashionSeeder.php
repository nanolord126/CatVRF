<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Fashion\Models\FashionProduct;
use App\Domains\Fashion\Models\FashionOrder;
use App\Domains\Fashion\Models\FashionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FashionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Fashion vertical...');

            $categories = ['clothing', 'shoes', 'accessories', 'bags'];
            
            foreach ($categories as $category) {
                FashionCategory::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => ucfirst($category),
                    'slug' => Str::slug($category),
                    'description' => "Fashion {$category} category",
                ]);
            }

            for ($i = 1; $i <= 30; $i++) {
                FashionProduct::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'category_id' => rand(1, 4),
                    'name' => "Fashion Product {$i}",
                    'description' => "Description for fashion product {$i}",
                    'price' => rand(500, 50000),
                    'stock' => rand(10, 100),
                    'brand' => "Brand {$i}",
                    'size' => ['XS', 'S', 'M', 'L', 'XL'][rand(0, 4)],
                    'color' => ['red', 'blue', 'black', 'white', 'green'][rand(0, 4)],
                    'status' => 'available',
                ]);
            }

            $this->command->info('Fashion vertical seeded successfully.');
        });
    }
}
