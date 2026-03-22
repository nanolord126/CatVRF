<?php declare(strict_types=1);

namespace App\Domains\ConstructionMaterials\Services;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

final class MaterialCalculatorService
{
    public function __construct()
    {
    }

    public function calculateMaterialNeeds(
        int $areaM2,
        string $materialType,
        ?float $thickness = null,
        string $correlationId = '',
    ): array {




        try {
            $calculator = DB::table('material_calculators')
                ->where('material_type', $materialType)
                ->first();

            if (!$calculator) {
                throw new \Exception("Calculator not found for $materialType");
            }

            // Базовый расчёт
            $quantity = $areaM2 * $calculator->consumption_per_m2;
            if ($thickness) {
                $quantity = $quantity * $thickness;
            }

            $result = [
                'material_type' => $materialType,
                'quantity' => $quantity,
                'unit' => $calculator->unit,
                'estimated_cost' => $quantity * $calculator->unit_price,
            ];

            Log::channel('audit')->info('Material calculation completed', [
                'area_m2' => $areaM2,
                'material_type' => $materialType,
                'quantity' => $quantity,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Material calculation failed', [
                'material_type' => $materialType,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
