<?php

declare(strict_types=1);

namespace App\Services\AI;

use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class AIPricingCalculatorService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Калькулятор для вертикали Furniture — расчёт стоимости ремонта
     */
    public function calculateFurnitureRepairCost(array $items): array
    {
        $baseCost = 0;
        $laborCost = 0;
        $materials = [];

        foreach ($items as $item) {
            $baseCost += $item['price'] * $item['quantity'];

            // Добавить трудозатраты
            $laborCost += $item['repair_hours'] * 500;  // 500 руб/час

            // Материалы
            $materials[] = [
                'name' => $item['material'],
                'quantity' => $item['quantity'],
                'cost' => $item['material_cost'],
            ];
        }

        $totalCost = $baseCost + $laborCost;
        $discount = $this->getVolumeDiscount($baseCost, CarbonImmutable::now());

        return [
            'base_cost' => $baseCost,
            'labor_cost' => $laborCost,
            'materials' => $materials,
            'discount' => $discount,
            'total' => max((int) ($totalCost - $discount), 0),
        ];
    }

    /**
     * Калькулятор для вертикали Beauty — стоимость услуг
     */
    public function calculateBeautyServiceCost(array $services, bool $isFirstTime = false): array
    {
        $total = 0;

        foreach ($services as $service) {
            $cost = $service['base_price'] * $service['duration_multiplier'];
            $total += $cost;
        }

        // Скидка для новых клиентов
        $discount = $isFirstTime ? (int) ($total * 0.1) : 0;

        return [
            'services' => $services,
            'subtotal' => $total,
            'discount' => $discount,
            'total' => max((int) ($total - $discount), 0),
        ];
    }

    /**
     * Калькулятор для вертикали Food — стоимость меню
     */
    public function calculateMenuCost(array $menuItems, bool $includeDelivery = true): array
    {
        $ingredientsCost = 0;
        $preparationCost = 0;

        foreach ($menuItems as $item) {
            $ingredientsCost += ($item['ingredient_cost'] ?? $item['price'] * 0.6) * ($item['quantity'] ?? 1);
            $preparationCost += ($item['preparation_cost'] ?? $item['price'] * 0.4) * ($item['quantity'] ?? 1);
        }

        $baseTotal = $ingredientsCost + $preparationCost;
        $deliveryCost = $includeDelivery ? 300 : 0; // Базовая стоимость доставки
        $subtotal = $baseTotal + $deliveryCost;
        $discount = $this->getVolumeDiscount($subtotal, CarbonImmutable::now());

        return [
            'ingredients_cost' => $ingredientsCost,
            'preparation_cost' => $preparationCost,
            'delivery_cost' => $deliveryCost,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max($subtotal - $discount, 0),
            'currency' => 'RUB',
        ];
    }

    private function getVolumeDiscount(int $baseCost, CarbonImmutable $now): int
    {
        return match (true) {
            $baseCost > 50000 => (int) ($baseCost * 0.15),
            $baseCost > 20000 => (int) ($baseCost * 0.10),
            $baseCost > 10000 => (int) ($baseCost * 0.05),
            default => 0,
        };
    }
}
