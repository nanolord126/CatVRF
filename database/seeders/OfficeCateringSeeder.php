<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OfficeCateringSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table('tenants')->inRandomOrder()->value('id') ?? 1;

        $items = [
            ['name' => 'Бизнес-ланч базовый', 'sku' => 'OC-BAS001', 'meal_type' => 'lunch', 'servings' => 1, 'price_per_serving' => 25000, 'total_price' => 25000],
            ['name' => 'Обеденный набор премиум', 'sku' => 'OC-PRM002', 'meal_type' => 'lunch', 'servings' => 1, 'price_per_serving' => 40000, 'total_price' => 40000],
            ['name' => 'Завтрак Корпоратив', 'sku' => 'OC-BRK003', 'meal_type' => 'breakfast', 'servings' => 1, 'price_per_serving' => 18000, 'total_price' => 18000],
            ['name' => 'Комбо обед для компании', 'sku' => 'OC-CMB004', 'meal_type' => 'combo', 'servings' => 10, 'price_per_serving' => 28000, 'total_price' => 280000],
            ['name' => 'Паек здоровья', 'sku' => 'OC-HLT005', 'meal_type' => 'lunch', 'servings' => 1, 'price_per_serving' => 35000, 'total_price' => 35000],
            ['name' => 'Обед Йоги выходного', 'sku' => 'OC-YGL006', 'meal_type' => 'lunch', 'servings' => 5, 'price_per_serving' => 32000, 'total_price' => 160000],
            ['name' => 'Ужин вечерний офисный', 'sku' => 'OC-DNR007', 'meal_type' => 'dinner', 'servings' => 1, 'price_per_serving' => 30000, 'total_price' => 30000],
            ['name' => 'Снеки рабочий день', 'sku' => 'OC-SNK008', 'meal_type' => 'snacks', 'servings' => 20, 'price_per_serving' => 12000, 'total_price' => 240000],
            ['name' => 'Фрукты и ягоды набор', 'sku' => 'OC-FRT009', 'meal_type' => 'snacks', 'servings' => 15, 'price_per_serving' => 15000, 'total_price' => 225000],
            ['name' => 'Кофе и десерты', 'sku' => 'OC-DES010', 'meal_type' => 'snacks', 'servings' => 25, 'price_per_serving' => 8000, 'total_price' => 200000],
        ];

        foreach ($items as $item) {
            DB::table('office_caterings')->insert(array_merge($item, [
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'business_group_id' => null,
                'sku' => $item['sku'] . '-' . Str::random(4),
                'current_stock' => random_int(5, 50),
                'min_order' => random_int(1, 5),
                'rating' => random_int(42, 50) / 10,
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
