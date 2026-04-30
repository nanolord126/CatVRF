<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Modules\Restaurant\Domain\Entities\KitchenStation;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;
use Modules\Restaurant\Domain\Enums\OrderPriority;
use Modules\Restaurant\Domain\Enums\KitchenStationType;
use Modules\Restaurant\Domain\Repositories\KitchenStationRepositoryInterface;
use Modules\Restaurant\Domain\Repositories\OrderKitchenStatusRepositoryInterface;
use Modules\Restaurant\Domain\ValueObjects\PreparationTime;
use Modules\Restaurant\Application\DTOs\OrderItemDTO;

/**
 * KitchenService
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class KitchenService
{
    private const CACHE_TTL_SECONDS = 30;
    private const CACHE_TAG_PREFIX = 'kds';

    public function __construct(
        private KitchenStationRepositoryInterface $kitchenStationRepository,
        private OrderKitchenStatusRepositoryInterface $orderKitchenStatusRepository,
        private readonly CacheManager $cache,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Создать кухонную станцию
     */
    public function createStation(
        int $tenantId,
        string $name,
        KitchenStationType $type,
        ?string $description = null,
        ?int $displayOrder = null,
    ): KitchenStation {
        $station = KitchenStation::create(
            tenantId: $tenantId,
            name: $name,
            type: $type,
            description: $description,
            displayOrder: $displayOrder,
        );

        $this->kitchenStationRepository->save($station);
        $this->clearCache($tenantId);

        return $station;
    }

    /**
     * Получить все активные станции для tenant
     */
    public function getActiveStations(int $tenantId): Collection
    {
        $cacheKey = $this->getCacheKey('stations', $tenantId);

        return $this->cache->tags([$this->getCacheTag($tenantId)])
            ->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($tenantId) {
                return $this->kitchenStationRepository->findByTenantId($tenantId)
                    ->filter(fn (KitchenStation $station) => $station->isActive);
            });
    }

    /**
     * Получить станцию по ID
     */
    public function getStation(int $stationId): ?KitchenStation
    {
        return $this->kitchenStationRepository->findById($stationId);
    }

    /**
     * Получить активные заказы для станции
     */
    public function getActiveOrdersForStation(int $stationId): Collection
    {
        return $this->orderKitchenStatusRepository->findActiveOrdersForStation($stationId);
    }

    /**
     * Получить просроченные заказы
     */
    public function getOverdueOrders(): Collection
    {
        return $this->orderKitchenStatusRepository->getOverdueOrders();
    }

    /**
     * Отправить заказ на кухню
     */
    public function sendOrderToKitchen(
        int $orderId,
        int $kitchenStationId,
        PreparationTime $estimatedTime,
        OrderPriority $priority = OrderPriority::NORMAL,
        bool $isFromMarketplace = false,
        bool $isVip = false,
    ): OrderKitchenStatus {
        $status = OrderKitchenStatus::create(
            orderId: $orderId,
            kitchenStationId: $kitchenStationId,
            estimatedPreparationTime: $estimatedTime,
            priority: $priority,
            isFromMarketplace: $isFromMarketplace,
            isVip: $isVip,
        );

        $this->orderKitchenStatusRepository->save($status);
        $this->clearStationCache($kitchenStationId);

        return $status;
    }

    /**
     * Изменить статус заказа на кухне
     */
    public function updateOrderStatus(int $orderKitchenStatusId, OrderKitchenStatusEnum $newStatus): OrderKitchenStatus
    {
        $status = $this->orderKitchenStatusRepository->findById($orderKitchenStatusId);
        
        if ($status === null) {
            throw new \InvalidArgumentException('Order kitchen status not found');
        }

        $updatedStatus = $status->withStatus($newStatus);
        $this->orderKitchenStatusRepository->save($updatedStatus);
        $this->clearStationCache($status->kitchenStationId);

        return $updatedStatus;
    }

    /**
     * Пометить заказ как "в работе"
     */
    public function startOrder(int $orderKitchenStatusId): OrderKitchenStatus
    {
        return $this->updateOrderStatus($orderKitchenStatusId, OrderKitchenStatusEnum::IN_PROGRESS);
    }

    /**
     * Пометить заказ как "готов"
     */
    public function completeOrder(int $orderKitchenStatusId): OrderKitchenStatus
    {
        return $this->updateOrderStatus($orderKitchenStatusId, OrderKitchenStatusEnum::READY);
    }

    /**
     * Пометить заказ как "выдан"
     */
    public function serveOrder(int $orderKitchenStatusId): OrderKitchenStatus
    {
        return $this->updateOrderStatus($orderKitchenStatusId, OrderKitchenStatusEnum::SERVED);
    }

    /**
     * Отменить заказ на кухне
     */
    public function cancelOrder(int $orderKitchenStatusId): OrderKitchenStatus
    {
        return $this->updateOrderStatus($orderKitchenStatusId, OrderKitchenStatusEnum::CANCELLED);
    }

    /**
     * Пометить проблему с заказом
     */
    public function reportProblem(int $orderKitchenStatusId, string $comment): OrderKitchenStatus
    {
        $status = $this->orderKitchenStatusRepository->findById($orderKitchenStatusId);
        
        if ($status === null) {
            throw new \InvalidArgumentException('Order kitchen status not found');
        }

        $updatedStatus = $status->withProblem($comment);
        $this->orderKitchenStatusRepository->save($updatedStatus);
        $this->clearStationCache($status->kitchenStationId);

        return $updatedStatus;
    }

    /**
     * Изменить приоритет заказа
     */
    public function updateOrderPriority(int $orderKitchenStatusId, OrderPriority $priority): OrderKitchenStatus
    {
        $status = $this->orderKitchenStatusRepository->findById($orderKitchenStatusId);
        
        if ($status === null) {
            throw new \InvalidArgumentException('Order kitchen status not found');
        }

        $updatedStatus = $status->withPriority($priority);
        $this->orderKitchenStatusRepository->save($updatedStatus);
        $this->clearStationCache($status->kitchenStationId);

        return $updatedStatus;
    }

    /**
     * Получить статистику по кухне
     */
    public function getKitchenStats(int $tenantId): array
    {
        $cacheKey = $this->getCacheKey('stats', $tenantId);

        return $this->cache->tags([$this->getCacheTag($tenantId)])
            ->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($tenantId) {
                $orders = $this->orderKitchenStatusRepository->getOrdersByPriority($tenantId);

                return [
                    'total_active' => $orders->count(),
                    'pending' => $orders->filter(fn ($o) => $o->status === OrderKitchenStatusEnum::PENDING)->count(),
                    'in_progress' => $orders->filter(fn ($o) => $o->status === OrderKitchenStatusEnum::IN_PROGRESS)->count(),
                    'ready' => $orders->filter(fn ($o) => $o->status === OrderKitchenStatusEnum::READY)->count(),
                    'problem' => $orders->filter(fn ($o) => $o->status === OrderKitchenStatusEnum::PROBLEM)->count(),
                    'overdue' => $orders->filter(fn ($o) => $o->isOverdue())->count(),
                    'vip_orders' => $orders->filter(fn ($o) => $o->isVip)->count(),
                    'marketplace_orders' => $orders->filter(fn ($o) => $o->isFromMarketplace)->count(),
                ];
            });
    }

    /**
     * Автоматическая маршрутизация заказа на правильную станцию
     */
    public function routeOrderToStation(
        int $orderId,
        Collection $orderItems,
        int $tenantId,
        bool $isFromMarketplace = false,
        bool $isVip = false,
    ): Collection {
        $statuses = collect();

        $this->db->transaction(function () use ($orderId, $orderItems, $tenantId, $isFromMarketplace, $isVip, &$statuses) {
            $stations = $this->getActiveStations($tenantId);
            $stationMap = $stations->keyBy(fn ($s) => $s->type->value);

            foreach ($orderItems as $item) {
                $stationType = $this->determineStationType($item);
                $station = $stationMap->get($stationType->value);

                if ($station === null) {
                    continue;
                }

                $estimatedTime = $this->getEstimatedPreparationTime($item, $station->id);
                $priority = $this->determinePriority($item, $isVip, $isFromMarketplace);

                $status = $this->sendOrderToKitchen(
                    orderId: $orderId,
                    kitchenStationId: $station->id,
                    estimatedTime: $estimatedTime,
                    priority: $priority,
                    isFromMarketplace: $isFromMarketplace,
                    isVip: $isVip,
                );

                $statuses->push($status);
            }
        });

        return $statuses;
    }

    private function determineStationType(OrderItemDTO $item): KitchenStationType
    {
        return match ($item->category) {
            'drink', 'cocktail', 'beer', 'wine' => KitchenStationType::BAR,
            'dessert', 'cake', 'ice_cream' => KitchenStationType::DESSERT,
            'salad', 'cold_appetizer' => KitchenStationType::COLD,
            'grill', 'meat', 'steak' => KitchenStationType::GRILL,
            'pizza' => KitchenStationType::PIZZA,
            'sushi', 'roll' => KitchenStationType::SUSHI,
            default => KitchenStationType::HOT,
        };
    }

    private function getEstimatedPreparationTime(OrderItemDTO $item, int $stationId): PreparationTime
    {
        // TODO: Get from menu_item_preparation_times table
        return new PreparationTime($item->preparationMinutes ?? 15);
    }

    private function determinePriority(OrderItemDTO $item, bool $isVip, bool $isFromMarketplace): OrderPriority
    {
        if ($isVip) {
            return OrderPriority::VIP;
        }

        if ($isFromMarketplace) {
            return OrderPriority::HIGH;
        }

        if ($item->isUrgent ?? false) {
            return OrderPriority::URGENT;
        }

        return OrderPriority::NORMAL;
    }

    private function getCacheKey(string $type, int $tenantId): string
    {
        return sprintf('%s:%s:%d', self::CACHE_TAG_PREFIX, $type, $tenantId);
    }

    private function getCacheTag(int $tenantId): string
    {
        return sprintf('%s:tenant:%d', self::CACHE_TAG_PREFIX, $tenantId);
    }

    private function clearCache(int $tenantId): void
    {
        $this->cache->tags([$this->getCacheTag($tenantId)])->flush();
    }

    private function clearStationCache(int $stationId): void
    {
        $station = $this->kitchenStationRepository->findById($stationId);
        if ($station !== null) {
            $this->clearCache($station->tenantId);
        }
    }
}
