<?php

declare(strict_types=1);

namespace App\Domains\HouseholdGoods\HomeAppliance\Services;

use App\Domains\HouseholdGoods\HomeAppliance\Models\ApplianceRepairOrder;
use App\Domains\HouseholdGoods\HomeAppliance\Models\AppliancePart;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ApplianceRepairService — Канон 2026 (Production Ready).
 * Бизнес-логика обслуживания бытовой техники.
 * Списание запчастей, расчет гарантии, B2C/B2B флоу.
 */
final readonly class ApplianceRepairService
{
    public function __construct(
        private FraudControlService $fraudControl,
        private string $correlationId = ""
    ) {}

    /**
     * Запуск процесса ремонта (Диагностика -> Ремонт).
     * @param array $partsData [['part_id' => 1, 'quantity' => 2], ...]
     */
    public function startRepair(ApplianceRepairOrder $order, array $partsData, int $laborCost): ApplianceRepairOrder
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($order, $partsData, $laborCost, $correlationId) {
            // 1. Fraud Check
            $this->fraudControl->check(['operation' => 'appliance_repair_start', 'order_id' => $order->id]);

            $partsCost = 0;

            // 2. Списание запчастей
            foreach ($partsData as $data) {
                $part = AppliancePart::lockForUpdate()->find($data['part_id']);
                
                if (!$part || $part->stock_quantity < $data['quantity']) {
                    throw new \RuntimeException("Недостаточно запчастей на складе: " . ($part->name ?? 'ID:'.$data['part_id']));
                }

                $part->decrement('stock_quantity', $data['quantity']);
                $partsCost += $part->price_kopecks * $data['quantity'];

                $order->parts()->attach($part->id, [
                    'quantity' => $data['quantity'],
                    'price_at_moment_kopecks' => $part->price_kopecks,
                    'correlation_id' => $correlationId
                ]);
            }

            // 3. Обновление заказа
            $order->update([
                'status' => 'in_repair',
                'repair_started_at' => now(),
                'labor_cost_kopecks' => $laborCost,
                'parts_cost_kopecks' => $partsCost,
                'total_cost_kopecks' => $laborCost + $partsCost,
                'correlation_id' => $correlationId
            ]);

            Log::channel('audit')->info('HomeAppliance repair started', [
                'order_uuid' => $order->uuid,
                'parts_count' => count($partsData),
                'total_cost' => $order->total_cost_kopecks,
                'correlation_id' => $correlationId
            ]);

            return $order;
        });
    }

    /**
     * Завершение ремонта + Расчет гарантии.
     */
    public function completeRepair(ApplianceRepairOrder $order): ApplianceRepairOrder
    {
        return DB::transaction(function () use ($order) {
            // Гарантия 2026: 180 дней для B2C, 90 дней для B2B (по умолчанию)
            $warrantyDays = $order->is_b2b ? 90 : 180;

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
                'warranty_expires_at' => now()->addDays($warrantyDays),
                'correlation_id' => $this->correlationId ?: Str::uuid()
            ]);

            Log::channel('audit')->info('HomeAppliance repair completed', [
                'order_uuid' => $order->uuid,
                'warranty_until' => $order->warranty_expires_at->toDateTimeString(),
            ]);

            return $order;
        });
    }

    /**
     * Планирование выезда мастера.
     */
    public function scheduleVisit(ApplianceRepairOrder $order, Carbon $visitAt): ApplianceRepairOrder
    {
        $this->fraudControl->check(['operation' => 'appliance_schedule_visit', 'order_id' => $order->id]);

        $order->update([
            'visit_scheduled_at' => $visitAt,
            'status' => 'diagnostic'
        ]);

        return $order;
    }
}
