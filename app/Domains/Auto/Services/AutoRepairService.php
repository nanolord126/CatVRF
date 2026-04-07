<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use Carbon\Carbon;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class AutoRepairService
{

    public function __construct(private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создание нового заказ-наряда (СТО).
         */
        public function createRepairOrder(array $data, string $correlationId): AutoRepairOrder
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                // 1. Предварительная проверка фрода (защита от массовой записи ботами)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'repair_order_creation', amount: 0, correlationId: $correlationId ?? '');

                // 2. Проверка существования авто
                $vehicle = AutoVehicle::findOrFail($data['auto_vehicle_id']);

                // 3. Создание заказа
                $order = AutoRepairOrder::create(array_merge($data, [
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'planned_at' => $data['planned_at'] ?? Carbon::now()->addDay(),
                ]));

                $this->logger->info('Repair Order created', [
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
            $this->db->transaction(function () use ($order, $service, $hourlyRateKopecks) {
                $laborCost = $service->calculateLaborCost($hourlyRateKopecks);

                $order->increment('labor_cost_kopecks', $laborCost);
                $order->recalculateTotal();
                $order->save();

                $this->logger->info('Service added to order', [
                    'order_uuid' => $order->uuid,
                    'service_uuid' => $service->uuid,
                    'labor_cost' => $laborCost,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
            });
        }

        /**
         * Завершение ремонта и финализация расчетов (Billing Integration).
         */
        public function completeRepair(AutoRepairOrder $order): void
        {
            $this->db->transaction(function () use ($order) {
                $order->status = 'completed';
                $order->finished_at = Carbon::now();
                $order->save();

                // В реальной системе здесь инициируется WalletService::debit()
                // или генерация счета на оплату.

                $this->logger->info('Repair Order completed', [
                    'uuid' => $order->uuid,
                    'total_cost' => $order->total_cost_kopecks,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
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
