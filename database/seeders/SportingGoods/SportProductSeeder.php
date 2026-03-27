<?php

declare(strict_types=1);

namespace Database\Seeders\SportingGoods;

use App\Domains\Sports\SportingGoods\Models\SportProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class SportProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $products = [
            ['name' => 'Мяч футбольный профессиональный', 'sport_type' => 'football', 'price' => 80000],
            ['name' => 'Ракетка теннисная Wilson', 'sport_type' => 'tennis', 'price' => 150000],
            ['name' => 'Кроссовки для бега Asics', 'sport_type' => 'running', 'price' => 120000],
            ['name' => 'Гантели 10kg (пара)', 'sport_type' => 'gym', 'price' => 100000],
            ['name' => 'Шлем велосипедный', 'sport_type' => 'cycling', 'price' => 60000],
            ['name' => 'Очки для плавания', 'sport_type' => 'swimming', 'price' => 25000],
            ['name' => 'Мяч баскетбольный', 'sport_type' => 'basketball', 'price' => 70000],
            ['name' => 'Палатка туристическая', 'sport_type' => 'outdoor', 'price' => 200000],
            ['name' => 'Спортивная сумка Nike', 'sport_type' => 'gym', 'price' => 50000],
            ['name' => 'Скакалка профессиональная', 'sport_type' => 'running', 'price' => 15000],
        ];

        foreach ($products as $product) {
            SportProduct::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $product['name'],
                ],
                [
                    'sku' => strtoupper('SPORT-' . Str::random(8)),
                    'sport_type' => $product['sport_type'],
                    'description' => 'Спортивный товар высокого качества',
                    'price' => $product['price'],
                    'current_stock' => rand(30, 150),
                    'size_range' => json_encode(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
                    'rating' => round(rand(35, 50) / 10, 1),
                    'review_count' => rand(50, 300),
                    'status' => 'active',
                    'correlation_id' => Str::uuid()->toString(),
                    'tags' => ['sport', $product['sport_type']],
                ]
            );
        }
    }
}
