<?php

declare(strict_types=1);

namespace Database\Seeders\Confectionery;

use App\Domains\Confectionery\Models\ConfectioneryProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ConfectionerySeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $items = [
            ['name' => 'Торт Птичье молоко', 'category' => 'cake', 'price' => 120000],
            ['name' => 'Эклер шоколадный', 'category' => 'pastry', 'price' => 45000],
            ['name' => 'Шоколад Линдор', 'category' => 'chocolate', 'price' => 80000],
            ['name' => 'Конфеты ассорти', 'category' => 'candy', 'price' => 90000],
            ['name' => 'Печенье масляное', 'category' => 'biscuit', 'price' => 35000],
            ['name' => 'Печенье Орео', 'category' => 'cookies', 'price' => 60000],
            ['name' => 'Пирожное Тирамису', 'category' => 'pastry', 'price' => 75000],
            ['name' => 'Кекс с фруктами', 'category' => 'cake', 'price' => 50000],
            ['name' => 'Карамель', 'category' => 'candy', 'price' => 40000],
            ['name' => 'Трюфель', 'category' => 'chocolate', 'price' => 100000],
        ];

        foreach ($items as $item) {
            ConfectioneryProduct::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $item['name']],
                [
                    'sku' => strtoupper('CONF-' . Str::random(8)),
                    'category' => $item['category'],
                    'description' => 'Кондитерское изделие',
                    'price' => $item['price'],
                    'current_stock' => rand(30, 150),
                    'shelf_life_days' => rand(5, 30),
                    'status' => 'active',
                    'correlation_id' => Str::uuid()->toString(),
                    'tags' => ['confectionery', $item['category']],
                ]
            );
        }
    }
}
