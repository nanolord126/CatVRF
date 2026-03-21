<?php

declare(strict_types=1);

namespace Database\Seeders\AutoParts;

use App\Domains\AutoParts\Models\AutoPart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class AutoPartSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $parts = [
            ['name' => 'Фильтр масляный', 'category' => 'engine', 'price' => 25000],
            ['name' => 'Амортизатор передний', 'category' => 'suspension', 'price' => 150000],
            ['name' => 'Тормозная колодка', 'category' => 'brakes', 'price' => 80000],
            ['name' => 'Аккумулятор', 'category' => 'electrical', 'price' => 200000],
            ['name' => 'Дворник', 'category' => 'body', 'price' => 30000],
            ['name' => 'Чехлы сиденья', 'category' => 'interior', 'price' => 120000],
            ['name' => 'Коврики', 'category' => 'accessories', 'price' => 50000],
            ['name' => 'Свечи зажигания', 'category' => 'engine', 'price' => 15000],
            ['name' => 'Воздушный фильтр', 'category' => 'engine', 'price' => 20000],
            ['name' => 'Тормозной диск', 'category' => 'brakes', 'price' => 100000],
        ];

        foreach ($parts as $part) {
            AutoPart::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $part['name']],
                [
                    'sku' => strtoupper('PART-' . Str::random(8)),
                    'category' => $part['category'],
                    'description' => 'Автомобильная запчасть',
                    'price' => $part['price'],
                    'current_stock' => rand(20, 100),
                    'status' => 'active',
                    'correlation_id' => Str::uuid()->toString(),
                    'tags' => ['autopart', $part['category']],
                ]
            );
        }
    }
}
