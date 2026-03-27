<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\ConstructionAndRepair\ConstructionAndRepair\ConstructionMaterials\Models\ConstructionMaterial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ConstructionMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            ['name' => 'Цемент М400 50кг', 'category' => 'cement', 'sku' => 'CEM-M400-50', 'price' => 35000, 'unit' => 'bag'],
            ['name' => 'Кирпич красный', 'category' => 'bricks', 'sku' => 'BRK-RED-250', 'price' => 1200, 'unit' => 'piece'],
            ['name' => 'Песок строительный', 'category' => 'sand', 'sku' => 'SND-BLD-1T', 'price' => 8000, 'unit' => 'ton'],
            ['name' => 'Щебень гранитный', 'category' => 'gravel', 'sku' => 'GRV-GRAN-1T', 'price' => 12000, 'unit' => 'ton'],
            ['name' => 'Арматура 10мм', 'category' => 'steel', 'sku' => 'STL-ARM-10', 'price' => 45000, 'unit' => 'ton'],
            ['name' => 'Доска обрезная', 'category' => 'timber', 'sku' => 'TBR-OBR-50', 'price' => 25000, 'unit' => 'meter'],
            ['name' => 'Краска акриловая', 'category' => 'paint', 'sku' => 'PNT-ACR-5L', 'price' => 55000, 'unit' => 'liter'],
            ['name' => 'Электродрель', 'category' => 'tools', 'sku' => 'TLS-DRILL-850', 'price' => 150000, 'unit' => 'piece'],
            ['name' => 'Гвозди 50мм', 'category' => 'hardware', 'sku' => 'HRD-NAIL-50', 'price' => 2000, 'unit' => 'box'],
            ['name' => 'Шурупы нержавеющие', 'category' => 'hardware', 'sku' => 'HRD-SCREW-SST', 'price' => 5000, 'unit' => 'box'],
        ];

        foreach ($materials as $material) {
            ConstructionMaterial::updateOrCreate(
                ['sku' => $material['sku']],
                [
                    'tenant_id' => 1,
                    'uuid' => Str::uuid(),
                    'correlation_id' => Str::uuid(),
                    'name' => $material['name'],
                    'category' => $material['category'],
                    'description' => 'Строительный материал: ' . $material['name'],
                    'price' => $material['price'],
                    'unit' => $material['unit'],
                    'current_stock' => rand(50, 500),
                    'min_stock_threshold' => 20,
                    'max_stock_threshold' => 1000,
                    'tags' => json_encode(['construction', $material['category']]),
                ]
            );
        }
    }
}
