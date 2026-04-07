<?php

declare(strict_types=1);

namespace App\Domains\AI\Services\Pricing;

/**
 * Class AIPricingCalculatorService
 *
 * Part of the AI vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\AI\Services\Pricing
 */
final readonly class AIPricingCalculatorService
{
    /**
     * Динамический расчет ремонта/сметы с учетом трудозатрат и материалов.
     */
    public function calculateFurnitureRepairCost(array $items): array
    {
        $baseCost = 0;
        $laborCost = 0;
        $materials = [];

        foreach ($items as $item) {
            $baseCost += $item['price'] * $item['quantity'];
            $laborCost += $item['repair_hours'] * 500;
            
            $materials[] = [
                'name' => $item['material'],
                'quantity' => $item['quantity'],
                'cost' => $item['material_cost'],
            ];
        }

        $totalCost = $baseCost + $laborCost;
        $discount = $this->getVolumeDiscount($baseCost);

        return [
            'base_cost' => $baseCost,
            'labor_cost' => $laborCost,
            'materials' => $materials,
            'discount' => $discount,
            'total' => max($totalCost - $discount, 0),
        ];
    }

    private function getVolumeDiscount(int $baseCost): int
    {
        return match (true) {
            $baseCost > 50000 => (int)($baseCost * 0.15),
            $baseCost > 20000 => (int)($baseCost * 0.10),
            $baseCost > 10000 => (int)($baseCost * 0.05),
            default => 0,
        };
    }
}
