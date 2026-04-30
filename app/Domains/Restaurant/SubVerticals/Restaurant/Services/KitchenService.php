<?php

declare(strict_types=1);

namespace Modules\Restaurant\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;
use Modules\Restaurant\Models\Order;
use Modules\Restaurant\Models\OrderItem;
use Modules\Restaurant\Enums\OrderStatus;
use Modules\Restaurant\Events\KitchenOrderReady;

/**
 * Kitchen Service — Сервис для управления кухней (KDS)
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Cache::tags for cache invalidation
 * - Audit logging
 */
final readonly class KitchenService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly string $correlationId = 'default',
    ) {}

    /**
     * Получить очередь заказов для кухни
     */
    public function getKitchenQueue(int $restaurantId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->cache->tags(['restaurant', 'kitchen', "restaurant:{$restaurantId}"])
            ->remember("restaurant:{$restaurantId}:kitchen_queue", 30, function () use ($restaurantId) {
                return Order::where('restaurant_id', $restaurantId)
                    ->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::PREPARING])
                    ->with(['items' => function ($query) {
                        $query->whereIn('kitchen_status', ['pending', 'preparing']);
                    }, 'table'])
                    ->orderBy('order_time', 'asc')
                    ->get();
            });
    }

    /**
     * Получить готовые к выдаче заказы
     */
    public function getReadyOrders(int $restaurantId): \Illuminate\Database\Eloquent\Collection
    {
        return Order::where('restaurant_id', $restaurantId)
            ->where('status', OrderStatus::READY)
            ->with(['items', 'table', 'guest'])
            ->orderBy('actual_ready_time', 'asc')
            ->get();
    }

    /**
     * Начать приготовление позиции
     */
    public function startItemPreparation(OrderItem $item): OrderItem
    {
        return $this->db->transaction(function () use ($item) {
            $item->startPreparing();
            $item->save();

            // Если все позиции в заказе готовятся, меняем статус заказа
            $order = $item->order;
            $this->updateOrderStatusBasedOnItems($order);

            // Очищаем кэш
            $this->clearKitchenCache($order->restaurant_id);

            return $item->fresh();
        });
    }

    /**
     * Отметить позицию как готовую
     */
    public function markItemReady(OrderItem $item): OrderItem
    {
        return $this->db->transaction(function () use ($item) {
            $item->markReady();
            $item->save();

            $order = $item->order;
            $this->updateOrderStatusBasedOnItems($order);

            // Если весь заказ готов, генерируем событие
            if ($this->isOrderFullyReady($order)) {
                $this->markOrderReady($order);
            }

            // Очищаем кэш
            $this->clearKitchenCache($order->restaurant_id);

            return $item->fresh();
        });
    }

    /**
     * Отметить позицию как поданную
     */
    public function markItemServed(OrderItem $item): OrderItem
    {
        return $this->db->transaction(function () use ($item) {
            $item->markServed();
            $item->save();

            // Очищаем кэш
            $this->clearKitchenCache($item->order->restaurant_id);

            return $item->fresh();
        });
    }

    /**
     * Отменить позицию
     */
    public function cancelItem(OrderItem $item, string $reason): OrderItem
    {
        return $this->db->transaction(function () use ($item, $reason) {
            $item->cancel($reason);
            $item->save();

            // Пересчитываем итоги заказа
            $order = $item->order;
            $order->calculateTotals();
            $order->save();

            // Очищаем кэш
            $this->clearKitchenCache($order->restaurant_id);

            return $item->fresh();
        });
    }

    /**
     * Отметить заказ как готовый
     */
    public function markOrderReady(Order $order): Order
    {
        return $this->db->transaction(function () use ($order) {
            $order->markReady();
            $order->save();

            // Генерируем событие
            event(new KitchenOrderReady($order, $this->correlationId));

            // Очищаем кэш
            $this->clearKitchenCache($order->restaurant_id);

            return $order->fresh();
        });
    }

    /**
     * Получить статистику кухни
     */
    public function getKitchenStatistics(int $restaurantId, ?string $date = null): array
    {
        $date = $date ?? today();

        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereDate('order_time', $date)
            ->get();

        $items = OrderItem::whereHas('order', function ($query) use ($restaurantId, $date) {
            $query->where('restaurant_id', $restaurantId)
                ->whereDate('order_time', $date);
        })->get();

        return [
            'total_orders' => $orders->count(),
            'total_items' => $items->count(),
            'average_preparation_time' => $orders->whereNotNull('actual_ready_time')
                ->map(fn($o) => $o->getPreparationTime())
                ->filter()
                ->avg(),
            'items_by_status' => $items->groupBy('kitchen_status')->map->count(),
            'orders_by_status' => $orders->groupBy('status.value')->map->count(),
        ];
    }

    /**
     * Получить просроченные заказы
     */
    public function getOverdueOrders(int $restaurantId): \Illuminate\Database\Eloquent\Collection
    {
        return Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::PREPARING])
            ->where('estimated_ready_time', '<', now())
            ->with(['items', 'table'])
            ->orderBy('estimated_ready_time', 'asc')
            ->get();
    }

    // ========================
    // PRIVATE METHODS
    // ========================

    private function updateOrderStatusBasedOnItems(Order $order): void
    {
        $items = $order->items;

        $pendingCount = $items->where('kitchen_status', 'pending')->count();
        $preparingCount = $items->where('kitchen_status', 'preparing')->count();
        $readyCount = $items->where('kitchen_status', 'ready')->count();

        if ($preparingCount > 0 && $order->status === OrderStatus::CONFIRMED) {
            $order->startPreparing();
        } elseif ($readyCount === $items->count() && $order->status !== OrderStatus::READY) {
            $this->markOrderReady($order);
        }

        $order->save();
    }

    private function isOrderFullyReady(Order $order): bool
    {
        $totalItems = $order->items->count();
        $readyItems = $order->items->where('kitchen_status', 'ready')->count();

        return $totalItems > 0 && $totalItems === $readyItems;
    }

    private function clearKitchenCache(int $restaurantId): void
    {
        $this->cache->tags(['restaurant', 'kitchen', "restaurant:{$restaurantId}"])->flush();
    }
}
