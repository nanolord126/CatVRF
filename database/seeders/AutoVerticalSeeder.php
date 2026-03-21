<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AutoVerticalSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table('tenants')->inRandomOrder()->value('id') ?? 1;

        $items = [
            ['name' => 'Шина зимняя R16', 'sku' => 'AUT-TR-R16W', 'category' => 'tires', 'description' => 'Шина зимняя шипованная', 'price' => 500000],
            ['name' => 'Масло моторное 5W-40 4л', 'sku' => 'AUT-OIL-5W40', 'category' => 'oil', 'description' => 'Синтетика', 'price' => 350000],
            ['name' => 'Фильтр масляный', 'sku' => 'AUT-FLT-OIL01', 'category' => 'filters', 'description' => 'Универсальный', 'price' => 50000],
        ];

        foreach ($items as $item) {
            DB::table('auto_parts')->insert(array_merge($item, [
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'business_group_id' => null,
                'sku' => $item['sku'] . '-' . Str::random(4),
                'current_stock' => random_int(10, 50),
                'status' => 'active',
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
