<?php declare(strict_types=1);

namespace App\Domains\HouseholdGoods\HomeAppliance\Services;

use Carbon\Carbon;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class ApplianceRepairService
{

    public function __construct(private FraudControlService $fraud,
            private string $correlationId = "",
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Запуск процесса ремонта (Диагностика -> Ремонт).
         * @param array $partsData [['part_id' => 1, 'quantity' => 2], ...]
         */
        public function startRepair(ApplianceRepairOrder $order, array $partsData, int $laborCost): ApplianceRepairOrder
        {
            $correlationId = $this->correlationId ?: (string) Str::uuid();

            return $this->db->transaction(function () use ($order, $partsData, $laborCost, $correlationId) {
                // 1. Fraud Check
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'appliance_repair_start', amount: 0, correlationId: $correlationId ?? '');

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
                    'repair_started_at' => Carbon::now(),
                    'labor_cost_kopecks' => $laborCost,
                    'parts_cost_kopecks' => $partsCost,
                    'total_cost_kopecks' => $laborCost + $partsCost,
                    'correlation_id' => $correlationId
                ]);

                $this->logger->info('HomeAppliance repair started', [
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
            return $this->db->transaction(function () use ($order) {
                // Гарантия 2026: 180 дней для B2C, 90 дней для B2B (по умолчанию)
                $warrantyDays = $order->is_b2b ? 90 : 180;

                $order->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::now(),
                    'warranty_expires_at' => Carbon::now()->addDays($warrantyDays),
                    'correlation_id' => $this->correlationId ?: Str::uuid()
                ]);

                $this->logger->info('HomeAppliance repair completed', [
                    'order_uuid' => $order->uuid,
                    'warranty_until' => $order->warranty_expires_at->toDateTimeString(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return $order;
            });
        }

        /**
         * Планирование выезда мастера.
         */
        public function scheduleVisit(ApplianceRepairOrder $order, Carbon $visitAt): ApplianceRepairOrder
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'appliance_schedule_visit', amount: 0, correlationId: $correlationId ?? '');

            $order->update([
                'visit_scheduled_at' => $visitAt,
                'status' => 'diagnostic'
            ]);

            return $order;
        }
}
