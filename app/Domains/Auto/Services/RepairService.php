<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\AutoRepairOrder;
use App\Domains\Auto\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: RepairService.
 * Бизнес-логика обслуживания на СТО.
 */
final readonly class RepairService
{
    /**
     * Открытие нового заказа на ремонт.
     */
    public function createOrder(Vehicle $vehicle, int $clientId, array $data, string $correlationId): AutoRepairOrder
    {
        return DB::transaction(function () use ($vehicle, $clientId, $data, $correlationId) {
            $data['uuid'] = (string) Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['vehicle_id'] = $vehicle->id;
            $data['client_id'] = $clientId;
            $data['status'] = 'pending';
            $data['correlation_id'] = $correlationId;

            $order = AutoRepairOrder::create($data);

            // Перевод авто в статус ремонта
            $vehicle->update(['status' => 'repair']);

            Log::channel('audit')->info('Repair order created', [
                'order_uuid' => $order->uuid,
                'vehicle_uuid' => $vehicle->uuid,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Обновление стоимости работ и добавление запчастей.
     */
    public function addPartsAndLabor(AutoRepairOrder $order, int $laborCost, array $parts, string $correlationId): void
    {
        DB::transaction(function () use ($order, $laborCost, $parts, $correlationId) {
            $order->labor_cost_kopecks = $laborCost;
            $order->parts_list = array_merge($order->parts_list ?? [], $parts);
            
            // Расчет стоимости запчастей
            $partsTotal = array_reduce($parts, fn($carry, $item) => $carry + $item['price'], 0);
            $order->parts_cost_kopecks = $partsTotal;
            
            $order->recalculateTotal();
            $order->correlation_id = $correlationId;
            $order->save();

            Log::channel('audit')->info('Repair costs updated', [
                'order_uuid' => $order->uuid,
                'labor' => $laborCost,
                'parts_count' => count($parts),
                'total' => $order->total_cost_kopecks,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Завершение ремонта.
     */
    public function completeOrder(AutoRepairOrder $order, string $mechanicReport, string $correlationId): void
    {
        DB::transaction(function () use ($order, $mechanicReport, $correlationId) {
            $order->update([
                'status' => 'completed',
                'mechanic_report' => $mechanicReport,
                'finished_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            // Возврат авто в статус "active"
            $order->vehicle->update(['status' => 'active']);

            Log::channel('audit')->info('Repair order completed', [
                'order_uuid' => $order->uuid,
                'vehicle_uuid' => $order->vehicle->uuid,
                'final_cost' => $order->total_cost_kopecks,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
