<?php

declare(strict_types=1);

namespace Database\Seeders\Gifts;

use App\Domains\PartySupplies\Gifts\Models\GiftProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class GiftSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $gifts = [
            ['name' => 'Роботизированный пылесос', 'category' => 'gadget', 'price' => 150000, 'occasion' => 'birthday'],
            ['name' => 'Премиум кофе-набор', 'category' => 'luxury', 'price' => 80000, 'occasion' => 'any'],
            ['name' => 'Часы смарт', 'category' => 'gadget', 'price' => 200000, 'occasion' => 'anniversary'],
            ['name' => 'Духи Dior Sauvage', 'category' => 'luxury', 'price' => 120000, 'occasion' => 'romantic'],
            ['name' => 'Набор конструктора LEGO', 'category' => 'kids', 'price' => 60000, 'occasion' => 'birthday'],
            ['name' => 'Портативная колонка', 'category' => 'gadget', 'price' => 50000, 'occasion' => 'any'],
            ['name' => 'Люксовый подарок (золото)', 'category' => 'luxury', 'price' => 500000, 'occasion' => 'wedding'],
            ['name' => 'Наушники Sony', 'category' => 'gadget', 'price' => 100000, 'occasion' => 'birthday'],
            ['name' => 'Набор спа', 'category' => 'romantic', 'price' => 70000, 'occasion' => 'anniversary'],
            ['name' => 'Детский велосипед', 'category' => 'kids', 'price' => 90000, 'occasion' => 'birthday'],
        ];

        foreach ($gifts as $gift) {
            GiftProduct::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $gift['name'],
                ],
                [
                    'sku' => strtoupper('GFT-' . Str::random(8)),
                    'category' => $gift['category'],
                    'occasion' => $gift['occasion'],
                    'description' => 'Идеальный подарок для любого случая',
                    'price' => $gift['price'],
                    'gift_wrap_available' => true,
                    'current_stock' => rand(20, 100),
                    'rating' => round(rand(35, 50) / 10, 1),
                    'review_count' => rand(50, 200),
                    'status' => 'active',
                    'correlation_id' => Str::uuid()->toString(),
                    'tags' => ['gift', $gift['occasion']],
                ]
            );
        }
    }
}
