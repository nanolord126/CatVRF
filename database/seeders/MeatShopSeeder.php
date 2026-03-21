<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\MeatShops\Models\MeatShop;
use Illuminate\Database\Seeder;

final class MeatShopSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Говядина стейк Рибай', 'sku' => 'MSH-BEF001', 'meat_type' => 'beef', 'cut' => 'steak', 'weight_g' => 1000, 'price' => 380000],
            ['name' => 'Свинина маринованная', 'sku' => 'MSH-PRK002', 'meat_type' => 'pork', 'cut' => 'ribs', 'weight_g' => 750, 'price' => 220000],
            ['name' => 'Курица домашняя филе', 'sku' => 'MSH-CHK003', 'meat_type' => 'chicken', 'cut' => 'fillet', 'weight_g' => 500, 'price' => 140000],
            ['name' => 'Говядина фарш', 'sku' => 'MSH-BEF004', 'meat_type' => 'beef', 'cut' => 'ground', 'weight_g' => 500, 'price' => 250000],
            ['name' => 'Баранина ягнёнка плечо', 'sku' => 'MSH-LMB005', 'meat_type' => 'lamb', 'cut' => 'shoulder', 'weight_g' => 750, 'price' => 320000],
            ['name' => 'Колбаса салями местная', 'sku' => 'MSH-SAL006', 'meat_type' => 'mixed', 'cut' => 'fillet', 'weight_g' => 250, 'price' => 180000],
            ['name' => 'Стейк филе миньон', 'sku' => 'MSH-BEF007', 'meat_type' => 'beef', 'cut' => 'steak', 'weight_g' => 500, 'price' => 420000],
            ['name' => 'Грудка куриная охл', 'sku' => 'MSH-CHK008', 'meat_type' => 'chicken', 'cut' => 'fillet', 'weight_g' => 1000, 'price' => 210000],
            ['name' => 'Свинина окорок', 'sku' => 'MSH-PRK009', 'meat_type' => 'pork', 'cut' => 'shoulder', 'weight_g' => 1000, 'price' => 260000],
            ['name' => 'Баранина фарш', 'sku' => 'MSH-LMB010', 'meat_type' => 'lamb', 'cut' => 'ground', 'weight_g' => 500, 'price' => 280000],
        ];

        foreach ($items as $item) {
            MeatShop::updateOrCreate(
                ['sku' => $item['sku'], 'tenant_id' => 1],
                array_merge($item, [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => 1,
                    'current_stock' => random_int(10, 100),
                    'is_certified' => true,
                    'rating' => random_int(42, 50) / 10,
                ])
            );
        }
    }
}
