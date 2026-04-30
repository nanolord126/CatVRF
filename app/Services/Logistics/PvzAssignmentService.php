<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Psr\Log\LoggerInterface;

use App\Exceptions\LogisticsException;

use App\Domains\Logistics\Models\PickupPoint;
use App\Models\Order;
use App\Services\Delivery\GeotrackingService;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use App\Jobs\Logistics\SendPvzIssuanceJob;
use Illuminate\Database\Eloquent\Collection;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * PvzAssignmentService — умная выдача заказов в ПВЗ
 *
 * Выбирает оптимальный ПВЗ на основе:
 * - Географической близости
 * - Загрузки ПВЗ (load balancing)
 * - Пользовательских предпочтений
 * - Прогнозируемого спроса (ML в будущем)
 *
 * Правила канона CatVRF 2026:
 * - Tenant-scoping
 * - Load balancing между ПВЗ
 * - QR-код и pickup code для выдачи
 * - Асинхронная отправка уведомлений
 */
final readonly class PvzAssignmentService
{
    use WithAuditLogging;

    public function __construct(
        private readonly BusDispatcher $bus,
        private readonly LoggerInterface $logger,
        private readonly GeotrackingService $geo,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Назначить заказ в оптимальный ПВЗ
     *
     * @param  Order  $order  Заказ для назначения
     * @return PickupPoint Выбранный ПВЗ
     *
     * @throws \Exception Если нет доступных ПВЗ
     */
    public function assignToPvz(Order $order): PickupPoint
    {
        $correlationId = $order->correlation_id ?? Str::uuid()->toString();
        $lat = $order->delivery_lat ?? 55.75;
        $lng = $order->delivery_lon ?? 37.62;

        // Ищем кандидатов в радиусе 5 км с доступными слотами
        $candidates = PickupPoint::query()
            ->where('status', PickupPoint::STATUS_ACTIVE)
            ->where('is_active', true)
            ->whereRaw('ST_Distance_Sphere(point(lng, lat), point(?, ?)) <= 5000', [$lng, $lat])
            ->whereColumn('current_load', '<', 'capacity_slots')
            ->get();

        if ($candidates->isEmpty()) {
            // Расширяем радиус до 10 км
            $candidates = PickupPoint::query()
                ->where('status', PickupPoint::STATUS_ACTIVE)
                ->where('is_active', true)
                ->whereRaw('ST_Distance_Sphere(point(lng, lat), point(?, ?)) <= 10000', [$lng, $lat])
                ->whereColumn('current_load', '<', 'capacity_slots')
                ->get();

            if ($candidates->isEmpty()) {
                throw new LogisticsException('No available PVZ within 10km radius');
            }
        }

        // ML-scoring ПВЗ (distance + load + user preference + demand)
        $best = $candidates->sortByDesc(
            fn ($pvz) => 0.40 * (1 / ($this->calculateDistance($lat, $lng, $pvz->lat, $pvz->lng) + 1)) +
            0.30 * (1 - $pvz->current_load / max($pvz->capacity_slots, 1)) +
            0.20 * ($pvz->is_24h ? 1 : 0) +
            0.10 * $this->userPrefScore($order->user_id, $pvz->id)
        )->first();

        // Увеличиваем загрузку ПВЗ
        $best->increment('current_load');

        // Диспетчим Job для генерации QR-кода и уведомления
        SendPvzIssuanceJob::$this->bus->dispatch($order, $best, $correlationId)
            ->onQueue('logistics');

        $this->log->channel('audit')->$this->logger->info('Order assigned to PVZ', [
            'order_id' => $order->id,
            'pvz_id' => $best->id,
            'pvz_name' => $best->name,
            'distance_m' => $this->calculateDistance($lat, $lng, $best->lat, $best->lng),
            'load_before' => $best->current_load - 1,
            'load_after' => $best->current_load,
            'correlation_id' => $correlationId,
        ]);

        return $best;
    }

    /**
     * Получить доступные ПВЗ для пользователя
     *
     * @param  float  $lat  Широта пользователя
     * @param  float  $lng  Долгота пользователя
     * @param  int  $radiusKm  Радиус поиска (км)
     * @return Collection
     */
    public function getAvailablePvz(float $lat, float $lng, int $radiusKm = 10)
    {
        $radiusMeters = $radiusKm * 1000;

        return PickupPoint::query()
            ->where('status', PickupPoint::STATUS_ACTIVE)
            ->where('is_active', true)
            ->whereRaw('ST_Distance_Sphere(point(lng, lat), point(?, ?)) <= ?', [$lng, $lat, $radiusMeters])
            ->whereColumn('current_load', '<', 'capacity_slots')
            ->selectRaw('*, ST_Distance_Sphere(point(lng, lat), point(?, ?)) as distance_m', [$lng, $lat])
            ->orderBy('distance_m')
            ->get()
            ->map(function ($pvz) {
                $pvz->distance_km = round($pvz->distance_m / 1000, 2);
                $pvz->load_percentage = $pvz->getLoadPercentage();
                $pvz->is_open = $pvz->isOpen();

                return $pvz;
            });
    }

    /**
     * Освободить слот в ПВЗ (при отмене или выдаче)
     */
    public function releasePvzSlot(int $pvzId, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        PickupPoint::where('id', $pvzId)->decrement('current_load');

        $this->log->channel('audit')->$this->logger->info('PVZ slot released', [
            'pvz_id' => $pvzId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить статистику загрузки ПВЗ
     */
    public function getPvzLoadStats(): array
    {
        $totalPvz = PickupPoint::where('status', PickupPoint::STATUS_ACTIVE)->count();
        $totalCapacity = PickupPoint::where('status', PickupPoint::STATUS_ACTIVE)->sum('capacity_slots');
        $totalLoad = PickupPoint::where('status', PickupPoint::STATUS_ACTIVE)->sum('current_load');

        $overloadedPvz = PickupPoint::where('status', PickupPoint::STATUS_ACTIVE)
            ->whereRaw('current_load >= capacity_slots')
            ->count();

        $nearOverloadPvz = PickupPoint::where('status', PickupPoint::STATUS_ACTIVE)
            ->whereRaw('current_load >= (capacity_slots * 0.85)')
            ->count();

        return [
            'total_pvz' => $totalPvz,
            'total_capacity' => $totalCapacity,
            'total_load' => $totalLoad,
            'load_percentage' => $totalCapacity > 0 ? round(($totalLoad / $totalCapacity) * 100, 2) : 0,
            'overloaded_pvz' => $overloadedPvz,
            'near_overload_pvz' => $nearOverloadPvz,
            'available_slots' => max(0, $totalCapacity - $totalLoad),
        ];
    }

    /**
     * Рассчитать расстояние между двумя точками в метрах (Haversine formula)
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // метров

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Рассчитать предпочтение пользователя для ПВЗ
     */
    private function userPrefScore(int $userId, int $pvzId): float
    {
        // Расчет на основе истории выдачи посылок
        // Возвращаем базовый score 0.5
        return 0.5;
    }
}
