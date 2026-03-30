<?php declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIPricingCalculatorService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
            $discount = $this->getVolumeDiscount($baseCost);

            return [
                'base_cost' => $baseCost,
                'labor_cost' => $laborCost,
                'materials' => $materials,
                'discount' => $discount,
                'total' => max((int)($totalCost - $discount), 0),
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
            $discount = $isFirstTime ? (int)($total * 0.1) : 0;

            return [
                'services' => $services,
                'subtotal' => $total,
                'discount' => $discount,
                'total' => max((int)($total - $discount), 0),
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
