<?php

declare(strict_types=1);

namespace Database\Seeders\Cosmetics;

use App\Domains\Cosmetics\Models\CosmeticProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CosmeticSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $products = [
            ['name' => 'Dior Lipstick Ruby', 'brand' => 'Dior', 'category' => 'lipstick', 'price' => 300000, 'cruelty_free' => true],
            ['name' => 'MAC Face & Body Foundation', 'brand' => 'MAC', 'category' => 'foundation', 'price' => 250000, 'cruelty_free' => true],
            ['name' => 'Maybelline Lash Sensational Mascara', 'brand' => 'Maybelline', 'category' => 'mascara', 'price' => 35000, 'cruelty_free' => false],
            ['name' => 'Chanel Blush Sublimage', 'brand' => 'Chanel', 'category' => 'blush', 'price' => 280000, 'cruelty_free' => true],
            ['name' => 'Bobbi Brown Eyeshadow Palette', 'brand' => 'Bobbi Brown', 'category' => 'eyeshadow', 'price' => 200000, 'cruelty_free' => true],
            ['name' => 'Clinique Clarifying Lotion', 'brand' => 'Clinique', 'category' => 'skincare', 'price' => 120000, 'cruelty_free' => true],
            ['name' => 'Tom Ford Noir Perfume', 'brand' => 'Tom Ford', 'category' => 'perfume', 'price' => 450000, 'cruelty_free' => true],
            ['name' => 'L\'Oréal Paris Nail Polish', 'brand' => 'L\'Oréal', 'category' => 'nail_polish', 'price' => 40000, 'cruelty_free' => false],
            ['name' => 'Estée Lauder Double Wear Foundation', 'brand' => 'Estée Lauder', 'category' => 'foundation', 'price' => 280000, 'cruelty_free' => true],
            ['name' => 'Rimmel London Mascara', 'brand' => 'Rimmel', 'category' => 'mascara', 'price' => 30000, 'cruelty_free' => false],
        ];

        foreach ($products as $product) {
            CosmeticProduct::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $product['name'],
                ],
                [
                    'sku' => strtoupper('COSM-' . Str::random(8)),
                    'brand' => $product['brand'],
                    'category' => $product['category'],
                    'description' => 'Премиум косметический продукт высокого качества',
                    'price' => $product['price'],
                    'current_stock' => rand(50, 500),
                    'min_stock_threshold' => 20,
                    'skin_type' => 'all',
                    'cruelty_free' => $product['cruelty_free'],
                    'natural' => rand(0, 1),
                    'rating' => round(rand(35, 50) / 10, 1),
                    'review_count' => rand(50, 500),
                    'status' => 'active',
                    'correlation_id' => Str::uuid()->toString(),
                    'tags' => ['cosmetics', strtolower($product['category'])],
                ]
            );
        }
    }
}
