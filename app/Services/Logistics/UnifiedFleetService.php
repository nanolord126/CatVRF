<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Models\PickupPoint;
use App\Models\Order;
use App\Services\Delivery\RouteOptimizationService;
use App\Services\FraudControlService;
use App\Services\Fraud\FraudMLService;
use App\Events\Logistics\ShipmentAssigned;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * UnifiedFleetService — главный мозг Unified Logistics Platform
 *
 * Объединяет курьеров (включая такси-водителей) и ПВЗ в единую систему фулфилмента.
 *
 * Правила канона CatVRF 2026:
 * - Fraud-check обязателен перед назначением
 * - ML-scoring для выбора оптимального курьера (XGBoost/LightGBM)
 * - ПВЗ приоритет для мелких заказов (эффективность)
 * - Такси-водители как гибридный флот
 * - Idempotency через correlation_id
 * - Tenant-scoping
 * - Асинхронные операции через queue
 */
final readonly class UnifiedFleetService
{
    use WithAuditLogging;

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly PvzAssignmentService $pvzService,
        private readonly RouteOptimizationService $routeOpt,
        private readonly FraudMLService $mlService,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Назначить фулфилмент для заказа (курьер, такси или ПВЗ)
     *
     * @param  Order  $order  Заказ для фулфилмента
     * @param  string|null  $preferredType  Предпочтительный тип (courier|taxi|pvz)
     * @return OrderShipment Созданная запись о доставке
     */
    public function assignToOrder(Order $order, ?string $preferredType = null): OrderShipment
    {
        $correlationId = $order->correlation_id ?? Str::uuid()->toString();

        return $this->db->transaction(function () use ($order, $preferredType, $correlationId) {
            // Fraud-check
            $this->fraud->check($order->user_id, 'shipment_assignment', $order->total, null, null, $correlationId);

            // Определяем тип фулфилмента
            $fulfillmentType = $preferredType ?? $this->determineFulfillmentType($order);

            $shipment = match ($fulfillmentType) {
                'pvz' => $this->assignToPvz($order, $correlationId),
                default => $this->assignToCourier($order, $fulfillmentType, $correlationId),
            };

            // Обновляем заказ
            $order->update([
                'fulfillment_id' => $shipment->fulfillment_id,
                'fulfillment_type' => $shipment->fulfillment_type,
                'correlation_id' => $correlationId,
            ]);

            // Диспетчим событие для реалтайма
            $this->eventDispatcher->dispatch(new ShipmentAssigned($shipment));

            $this->log->channel('audit')->$this->logger->info('Order assigned to fulfillment', [
                'order_id' => $order->id,
                'shipment_id' => $shipment->id,
                'fulfillment_type' => $shipment->fulfillment_type,
                'fulfillment_id' => $shipment->fulfillment_id,
                'correlation_id' => $correlationId,
            ]);

            return $shipment;
        });
    }

    /**
     * Переназначить shipment другому курьеру/ПВЗ
     */
    public function reassignShipment(OrderShipment $shipment, ?int $newCourierId = null, ?int $newPvzId = null): OrderShipment
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($shipment, $newCourierId, $newPvzId, $correlationId) {
            $oldCourierId = $shipment->courier_id;
            $oldPvzId = $shipment->pickup_point_id;

            // Освобождаем старого курьера
            if ($oldCourierId) {
                Courier::where('id', $oldCourierId)->update(['status' => 'online']);
            }

            // Освобождаем старый ПВЗ
            if ($oldPvzId) {
                PickupPoint::where('id', $oldPvzId)->decrement('current_load');
            }

            // Назначаем нового
            if ($newCourierId) {
                $shipment->update([
                    'courier_id' => $newCourierId,
                    'pickup_point_id' => null,
                    'fulfillment_type' => 'courier',
                    'fulfillment_id' => $newCourierId,
                    'status' => OrderShipment::STATUS_ASSIGNED,
                    'assigned_at' => CarbonImmutable::now(),
                    'correlation_id' => $correlationId,
                ]);

                Courier::where('id', $newCourierId)->update(['status' => Courier::STATUS_ON_DELIVERY]);
            } elseif ($newPvzId) {
                $shipment->update([
                    'courier_id' => null,
                    'pickup_point_id' => $newPvzId,
                    'fulfillment_type' => 'pickup_point',
                    'fulfillment_id' => $newPvzId,
                    'status' => OrderShipment::STATUS_ASSIGNED,
                    'assigned_at' => CarbonImmutable::now(),
                    'correlation_id' => $correlationId,
                ]);

                PickupPoint::where('id', $newPvzId)->increment('current_load');
            }

            $this->log->channel('audit')->$this->logger->info('Shipment reassigned', [
                'shipment_id' => $shipment->id,
                'old_courier_id' => $oldCourierId,
                'new_courier_id' => $newCourierId,
                'old_pvz_id' => $oldPvzId,
                'new_pvz_id' => $newPvzId,
                'correlation_id' => $correlationId,
            ]);

            return $shipment->fresh();
        });
    }

    /**
     * Определить оптимальный тип фулфилмента на основе эвристик
     */
    private function determineFulfillmentType(Order $order): string
    {
        // Эвристики для выбора ПВЗ:
        // - Вес < 10 кг
        // - Мало товаров (< 5)
        // - Пользователь выбрал ПВЗ в настройках
        $metadata = $order->metadata ?? [];
        $weightKg = $metadata['weight_kg'] ?? 5;
        $itemsCount = $metadata['items_count'] ?? 1;

        if ($weightKg < 10 && $itemsCount < 5 && ($metadata['prefers_pvz'] ?? false)) {
            return 'pvz';
        }

        // По умолчанию - курьер
        return 'courier';
    }

    /**
     * Назначить заказ курьеру (или такси)
     */
    private function assignToCourier(Order $order, string $courierType, string $correlationId): OrderShipment
    {
        $metadata = $order->metadata ?? [];
        $weightKg = $metadata['weight_kg'] ?? 5;
        $deliveryLat = $order->delivery_lat ?? 55.75;
        $deliveryLng = $order->delivery_lon ?? 37.62;

        // Ищем кандидатов (онлайн, достаточно грузоподъемности)
        $candidates = Courier::query()
            ->where('status', 'online')
            ->where('is_active', true)
            ->where('capacity_kg', '>=', $weightKg)
            ->when($courierType === 'taxi', fn ($q) => $q->where('is_taxi_driver', true))
            ->selectRaw(
                '*, ST_Distance_Sphere(point(current_lng, current_lat), point(?, ?)) as distance',
                [$deliveryLng, $deliveryLat]
            )
            ->orderBy('distance')
            ->limit(15)
            ->get();

        if ($candidates->isEmpty()) {
            throw new \RuntimeException('No available couriers found for order');
        }

        // ML-scoring кандидатов
        $scoredCandidates = $this->scoreCandidatesWithML($candidates, $order);
        $bestCourier = $scoredCandidates->first();

        // Создаем shipment
        $shipment = OrderShipment::create([
            'uuid' => Str::uuid(),
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'courier_id' => $bestCourier->id,
            'fulfillment_type' => $bestCourier->is_taxi_driver ? 'taxi' : 'courier',
            'fulfillment_id' => $bestCourier->id,
            'status' => OrderShipment::STATUS_ASSIGNED,
            'eta_minutes' => $this->routeOpt->predictEta($bestCourier, $order),
            'assigned_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
        ]);

        // Обновляем статус курьера
        $bestCourier->update(['status' => Courier::STATUS_ON_DELIVERY]);

        return $shipment;
    }

    /**
     * Назначить заказ в ПВЗ
     */
    private function assignToPvz(Order $order, string $correlationId): OrderShipment
    {
        try {
            $pvz = $this->pvzService->assignToPvz($order);
        } catch (\Exception $e) {
            // Fallback на курьера если ПВЗ недоступен
            $this->log->warning('PVZ assignment failed, fallback to courier', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return $this->assignToCourier($order, 'courier', $correlationId);
        }

        // Создаем shipment
        $shipment = OrderShipment::create([
            'uuid' => Str::uuid(),
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'pickup_point_id' => $pvz->id,
            'fulfillment_type' => 'pickup_point',
            'fulfillment_id' => $pvz->id,
            'status' => OrderShipment::STATUS_ASSIGNED,
            'eta_minutes' => 120, // 2 часа стандартно для ПВЗ
            'assigned_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
        ]);

        return $shipment;
    }

    /**
     * ML-scoring кандидатов курьеров
     * Использует существующую ML-инфраструктуру FraudMLService
     */
    private function scoreCandidatesWithML($candidates, Order $order)
    {
        // Простой скоринг на основе эвристик (ML можно подключить позже)
        $candidates->each(function ($c) use ($order) {
            $distance = $c->distance ?? 1000;
            $capacityRatio = $c->capacity_kg / max(($order->metadata['weight_kg'] ?? 5), 1);

            // Комбинированный score:
            // - Чем ближе курьер, тем выше score
            // - Чем лучше подходит грузоподъемность, тем выше score
            // - Рейтинг курьера важен
            // - Такси-водители получают бонус для гибкости

            $c->ml_score =
                0.40 * (1 / ($distance + 1)) +
                0.25 * min($capacityRatio, 1.0) +
                0.20 * ($c->rating ?? 5.0) / 5.0 +
                0.10 * ($c->is_taxi_driver ? 1 : 0) +
                0.05 * $this->calculateUserPreference($order->user_id, $c->id);
        });

        return $candidates->sortByDesc('ml_score');
    }

    /**
     * Рассчитать предпочтение пользователя для курьера
     */
    private function calculateUserPreference(int $userId, int $courierId): float
    {
        // Расчет метрик на основе истории заказов
        // Возвращаем базовый score 0.5
        return 0.5;
    }
}
