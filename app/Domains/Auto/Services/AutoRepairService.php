<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\AutoVehicle;
use App\Domains\Auto\Models\AutoRepairOrder;
use App\Domains\Auto\Models\AutoService;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AutoRepairService — Канон 2026.
 * 
 * Управление полным циклом ремонта: от записи до расчета итоговой сметы.
 */
final readonly class AutoRepairService
{
    public function __construct(
        private FraudControlService $fraudControl
    ) {}

    /**
     * Создание нового заказ-наряда (СТО).
     */
    public function createRepairOrder(array $data, string $correlationId): AutoRepairOrder
    {
        return DB::transaction(function () use ($data, $correlationId) {
            // 1. Предварительная проверка фрода (защита от массовой записи ботами)
            $this->fraudControl->check([
                'type' => 'repair_order_creation',
                'vehicle_id' => $data['auto_vehicle_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
            ]);

            // 2. Проверка существования авто
            $vehicle = AutoVehicle::findOrFail($data['auto_vehicle_id']);

            // 3. Создание заказа
            $order = AutoRepairOrder::create(array_merge($data, [
                'status' => 'pending',
                'correlation_id' => $correlationId,
                'planned_at' => $data['planned_at'] ?? now()->addDay(),
            ]));

            Log::channel('audit')->info('Repair Order created', [
                'uuid' => $order->uuid,
                'vin' => $vehicle->vin,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Добавление услуги в заказ-наряд с расчетом трудозатрат.
     */
    public function addServiceToOrder(AutoRepairOrder $order, AutoService $service, int $hourlyRateKopecks): void
    {
        DB::transaction(function () use ($order, $service, $hourlyRateKopecks) {
            $laborCost = $service->calculateLaborCost($hourlyRateKopecks);
            
            $order->increment('labor_cost_kopecks', $laborCost);
            $order->recalculateTotal();
            $order->save();

            Log::channel('audit')->info('Service added to order', [
                'order_uuid' => $order->uuid,
                'service_uuid' => $service->uuid,
                'labor_cost' => $laborCost,
            ]);
        });
    }

    /**
     * Завершение ремонта и финализация расчетов (Billing Integration).
     */
    public function completeRepair(AutoRepairOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order->status = 'completed';
            $order->finished_at = now();
            $order->save();

            // В реальной системе здесь инициируется WalletService::debit() 
            // или генерация счета на оплату.

            Log::channel('audit')->info('Repair Order completed', [
                'uuid' => $order->uuid,
                'total_cost' => $order->total_cost_kopecks,
            ]);
        });
    }

    /**
     * Получение истории ремонтов по VIN или конкретному авто.
     */
    public function getVehicleRepairHistory(string $vin): Collection
    {
        $vehicle = AutoVehicle::where('vin', $vin)->firstOrFail();
        
        return AutoRepairOrder::where('auto_vehicle_id', $vehicle->id)
            ->with(['vehicle'])
            ->orderBy('id', 'desc')
            ->get();
    }
}
