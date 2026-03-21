<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FurnitureSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table('tenants')->inRandomOrder()->value('id') ?? 1;

        $items = [
            ['name' => 'Диван Честерфилд кожаный', 'sku' => 'FRN-CHD001', 'category' => 'sofa', 'material' => 'leather', 'price' => 380000],
            ['name' => 'Кресло Папасан плетёное', 'sku' => 'FRN-CHS002', 'category' => 'chair', 'material' => 'fabric', 'price' => 120000],
            ['name' => 'Стол дубовый 6 мест', 'sku' => 'FRN-TBL003', 'category' => 'table', 'material' => 'wood', 'price' => 250000],
            ['name' => 'Кровать платформа берёза', 'sku' => 'FRN-BED004', 'category' => 'bed', 'material' => 'wood', 'price' => 180000],
            ['name' => 'Шкаф купе зеркальный', 'sku' => 'FRN-CAB005', 'category' => 'cabinet', 'material' => 'metal', 'price' => 200000],
            ['name' => 'Полка настенная керамика', 'sku' => 'FRN-SHF006', 'category' => 'shelf', 'material' => 'ceramic', 'price' => 85000],
            ['name' => 'Кресло офисное сетка', 'sku' => 'FRN-CHS007', 'category' => 'chair', 'material' => 'fabric', 'price' => 95000],
            ['name' => 'Тумба прикроватная дуб', 'sku' => 'FRN-TMB008', 'category' => 'cabinet', 'material' => 'wood', 'price' => 75000],
            ['name' => 'Диван раскладной серый', 'sku' => 'FRN-CHD009', 'category' => 'sofa', 'material' => 'fabric', 'price' => 145000],
            ['name' => 'Стол журнальный мрамор', 'sku' => 'FRN-TBL010', 'category' => 'table', 'material' => 'ceramic', 'price' => 120000],
        ];

        foreach ($items as $item) {
            DB::table('furnitures')->insert(array_merge($item, [
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'business_group_id' => null,
                'sku' => $item['sku'] . '-' . Str::random(4),
                'current_stock' => random_int(2, 50),
                'rating' => random_int(40, 50) / 10,
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
