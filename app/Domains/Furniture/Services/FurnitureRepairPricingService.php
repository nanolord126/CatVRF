<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Furniture Repair Pricing Calculator Service
 *
 * Part of the Furniture vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final readonly class FurnitureRepairPricingService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Динамический расчет ремонта/сметы с учетом трудозатрат и материалов.
     */
    public function calculateFurnitureRepairCost(array $items): array
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'furniture_pricing_calculate',
            amount: 0,
            correlationId: $correlationId ?? ''
        );

        $result = $this->db->transaction(function () use ($items, $correlationId) {
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

            $calculation = [
                'base_cost' => $baseCost,
                'labor_cost' => $laborCost,
                'materials' => $materials,
                'discount' => $discount,
                'total' => max($totalCost - $discount, 0),
            ];

            $this->logger->info('Furniture pricing calculation completed', [
                'correlation_id' => $correlationId,
                'total_cost' => $calculation['total'],
            ]);

            $this->audit->log('pricing_calculated', 'FurnitureRepairPricing', null, [], $calculation, $correlationId);

            return $calculation;
        });

        return $result;
    }

    private function getVolumeDiscount(int $baseCost): int
    {
        return match (true) {
            $baseCost > 50000 => (int) ($baseCost * 0.15),
            $baseCost > 20000 => (int) ($baseCost * 0.10),
            $baseCost > 10000 => (int) ($baseCost * 0.05),
            default => 0,
        };
    }
}
