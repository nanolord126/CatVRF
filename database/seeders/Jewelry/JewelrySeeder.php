<?php

declare(strict_types=1);

namespace Database\Seeders\Jewelry;

use App\Domains\Luxury\Jewelry\Models\JewelryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class JewelrySeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $items = [
            ['name' => 'Золотое кольцо с бриллиантом', 'category' => 'ring', 'metal' => 'gold', 'price' => 500000, 'weight' => 3.5, 'purity' => '750'],
            ['name' => 'Серебряное ожерелье', 'category' => 'necklace', 'metal' => 'silver', 'price' => 80000, 'weight' => 5.2, 'purity' => '925'],
            ['name' => 'Платиновый браслет', 'category' => 'bracelet', 'metal' => 'platinum', 'price' => 800000, 'weight' => 4.1, 'purity' => '950'],
            ['name' => 'Серьги с жемчугом', 'category' => 'earring', 'metal' => 'rose_gold', 'price' => 150000, 'weight' => 2.1, 'purity' => '750'],
            ['name' => 'Подвеска "Сердце"', 'category' => 'pendant', 'metal' => 'white_gold', 'price' => 120000, 'weight' => 1.8, 'purity' => '750'],
            ['name' => 'Часы швейцарские', 'category' => 'watch', 'metal' => 'gold', 'price' => 1200000, 'weight' => 45, 'purity' => '750'],
            ['name' => 'Колечко из серебра', 'category' => 'ring', 'metal' => 'silver', 'price' => 50000, 'weight' => 2.0, 'purity' => '925'],
            ['name' => 'Платиновая цепь', 'category' => 'necklace', 'metal' => 'platinum', 'price' => 950000, 'weight' => 12, 'purity' => '950'],
            ['name' => 'Браслет из золота 585', 'category' => 'bracelet', 'metal' => 'gold', 'price' => 200000, 'weight' => 8.5, 'purity' => '585'],
            ['name' => 'Кольцо с сапфиром', 'category' => 'ring', 'metal' => 'platinum', 'price' => 950000, 'weight' => 4.2, 'purity' => '950'],
        ];

        foreach ($items as $item) {
            JewelryItem::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $item['name'],
                ],
                [
                    'sku' => strtoupper('JWL-' . Str::random(8)),
                    'category' => $item['category'],
                    'metal' => $item['metal'],
                    'description' => 'Ювелирное изделие высокого качества',
                    'price' => $item['price'],
                    'weight_grams' => $item['weight'],
                    'purity' => $item['purity'],
                    'current_stock' => rand(1, 5),
                    'certificate_required' => $item['price'] > 200000,
                    'certificate_type' => rand(0, 1) ? 'GIA' : 'IGI',
                    'rating' => round(rand(40, 50) / 10, 1),
                    'review_count' => rand(30, 200),
                    'status' => 'active',
                    'correlation_id' => Str::uuid()->toString(),
                    'tags' => ['jewelry', strtolower($item['metal'])],
                ]
            );
        }
    }
}
