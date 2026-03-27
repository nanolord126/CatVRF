<?php

declare(strict_types=1);

namespace Database\Seeders\MedicalSupplies;

use App\Domains\Pharmacy\MedicalSupplies\Models\MedicalSupply;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class MedicalSupplySeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $supplies = [
            ['name' => 'Стерильные перчатки нитриловые', 'category' => 'consumables', 'price' => 15000],
            ['name' => 'Шприцы инсулиновые', 'category' => 'syringes', 'price' => 20000],
            ['name' => 'Пластыри медицинские', 'category' => 'bandages', 'price' => 25000],
            ['name' => 'УЗИ аппарат портативный', 'category' => 'equipment', 'price' => 500000],
            ['name' => 'Ланцеты для прокола', 'category' => 'instruments', 'price' => 12000],
            ['name' => 'Повязки стерильные', 'category' => 'consumables', 'price' => 18000],
            ['name' => 'Кислородный концентратор', 'category' => 'equipment', 'price' => 800000],
            ['name' => 'Катетеры мочевые', 'category' => 'consumables', 'price' => 25000],
            ['name' => 'Тонометр цифровой', 'category' => 'equipment', 'price' => 75000],
            ['name' => 'Спирт дезинфектант', 'category' => 'consumables', 'price' => 10000],
        ];

        foreach ($supplies as $supply) {
            MedicalSupply::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $supply['name'],
                ],
                [
                    'sku' => strtoupper('MED-' . Str::random(8)),
                    'category' => $supply['category'],
                    'description' => 'Медицинские расходники и оборудование',
                    'price' => $supply['price'],
                    'current_stock' => rand(50, 300),
                    'min_stock_threshold' => 20,
                    'status' => 'active',
                    'correlation_id' => Str::uuid()->toString(),
                    'tags' => ['medical', $supply['category']],
                ]
            );
        }
    }
}
