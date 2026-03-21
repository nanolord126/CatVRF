<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ToysKidsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table('tenants')->inRandomOrder()->value('id') ?? 1;

        $items = [
            ['name' => 'Конструктор Мега Город', 'sku' => 'TOY-BLD001', 'category' => 'building', 'age_min' => 6, 'age_max' => 12, 'price' => 350000],
            ['name' => 'Мягкий Медведь 100см', 'sku' => 'TOY-PLH002', 'category' => 'plush', 'age_min' => 0, 'age_max' => 99, 'price' => 250000],
            ['name' => 'Настольная игра "Монополия"', 'sku' => 'TOY-BGM003', 'category' => 'board_game', 'age_min' => 8, 'age_max' => 99, 'price' => 180000],
            ['name' => 'Радиоуправляемая машина', 'sku' => 'TOY-VEH004', 'category' => 'vehicle', 'age_min' => 6, 'age_max' => 15, 'price' => 450000],
            ['name' => 'Пазл "Космос" 1000 дет', 'sku' => 'TOY-PZL005', 'category' => 'puzzle', 'age_min' => 10, 'age_max' => 99, 'price' => 120000],
        ];

        foreach ($items as $item) {
            DB::table('toys_kids')->insert(array_merge($item, [
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'business_group_id' => null,
                'sku' => $item['sku'] . '-' . Str::random(4),
                'current_stock' => random_int(5, 100),
                'rating' => random_int(42, 50) / 10,
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
