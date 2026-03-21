<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PharmacySeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table('tenants')->inRandomOrder()->value('id') ?? 1;

        $items = [
            ['name' => 'Парацетамол', 'sku' => 'PHARM-PAR001', 'mnn' => 'paracetamol', 'form' => 'tablet', 'dosage' => '500 mg', 'price' => 5000, 'is_otc' => true],
            ['name' => 'Ибупрофен экспресс', 'sku' => 'PHARM-IBU002', 'mnn' => 'ibuprofen', 'form' => 'capsule', 'dosage' => '400 mg', 'price' => 12000, 'is_otc' => true],
            ['name' => 'Амоксициллин', 'sku' => 'PHARM-AMX003', 'mnn' => 'amoxicillin', 'form' => 'tablet', 'dosage' => '500 mg', 'price' => 15000, 'is_otc' => false],
            ['name' => 'Сироп от кашля', 'sku' => 'PHARM-CUG004', 'mnn' => 'ambroxol', 'form' => 'syrup', 'dosage' => '100 ml', 'price' => 25000, 'is_otc' => true],
            ['name' => 'Витамин C', 'sku' => 'PHARM-VITC05', 'mnn' => 'ascorbic acid', 'form' => 'tablet', 'dosage' => '1000 mg', 'price' => 8000, 'is_otc' => true],
        ];

        foreach ($items as $item) {
            DB::table('pharmacies')->insert(array_merge($item, [
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'business_group_id' => null,
                'sku' => $item['sku'] . '-' . Str::random(4),
                'current_stock' => random_int(50, 500),
                'requires_prescription' => !$item['is_otc'],
                'rating' => random_int(40, 50) / 10,
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
