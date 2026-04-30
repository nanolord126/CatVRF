<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Modules\Restaurant\Domain\Entities\OrderFulfillment;
use Modules\Restaurant\Domain\Entities\Order;
use Illuminate\Support\Facades\DB;

final class OrderFulfillmentService
{
    public function __construct(
        private readonly string $correlationId,
    ) {}

    public function createFulfillment(int $orderId, string $orderType, ?int $kitchenStationId = null): OrderFulfillment
    {
        $order = Order::findOrFail($orderId);

        return DB::transaction(function () use ($order, $orderType, $kitchenStationId) {
            $fulfillment = OrderFulfillment::create([
                'tenant_id' => $order->tenant_id,
                'restaurant_id' => $order->restaurant_id,
                'order_id' => $order->id,
                'order_type' => $orderType,
                'kitchen_station_id' => $kitchenStationId,
                'status' => 'pending',
                'priority' => $this->calculatePriority($orderType, $order->created_at),
                'items' => $order->items,
                'special_instructions' => $order->special_instructions,
                'allergies' => $order->allergies,
                'correlation_id' => $this->correlationId,
            ]);

            // Auto-assign to kitchen station if not specified
            if (!$kitchenStationId) {
                $this->autoAssignToStation($fulfillment);
            }

            return $fulfillment;
        });
    }

    public function assignToKitchen(int $fulfillmentId, int $chefId, ?int $stationId = null): OrderFulfillment
    {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);
        
        $fulfillment->update([
            'assigned_chef_id' => $chefId,
            'kitchen_station_id' => $stationId ?? $fulfillment->kitchen_station_id,
        ]);

        // Create task for chef
        \Modules\CatCRM\Domain\Entities\Task::create([
            'tenant_id' => $fulfillment->tenant_id,
            'assigned_to_id' => $chefId,
            'title' => "Приготовить заказ #{$fulfillment->order_id}",
            'type' => 'order_preparation',
            'priority' => $fulfillment->priority,
            'due_date' => now()->addMinutes(30),
            'entity_type' => 'order_fulfillment',
            'entity_id' => $fulfillment->id,
        ]);

        return $fulfillment->fresh();
    }

    public function assignToWaiter(int $fulfillmentId, int $waiterId): OrderFulfillment
    {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);
        
        $fulfillment->update(['assigned_waiter_id' => $waiterId]);

        return $fulfillment->fresh();
    }

    public function startPreparation(int $fulfillmentId): OrderFulfillment
    {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);
        $fulfillment->startPreparation();
        return $fulfillment->fresh();
    }

    public function markReady(int $fulfillmentId): OrderFulfillment
    {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);
        $fulfillment->markReady();

        // Notify waiter if assigned
        if ($fulfillment->assigned_waiter_id) {
            $this->notifyWaiter($fulfillment);
        }

        return $fulfillment->fresh();
    }

    public function markServed(int $fulfillmentId): OrderFulfillment
    {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);
        $fulfillment->markServed();
        return $fulfillment->fresh();
    }

    public function getFulfillmentStats(int $restaurantId, \DateTime $date): array
    {
        $fulfillments = OrderFulfillment::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $date)
            ->get();

        return [
            'total' => $fulfillments->count(),
            'pending' => $fulfillments->where('status', 'pending')->count(),
            'in_progress' => $fulfillments->where('status', 'in_progress')->count(),
            'ready' => $fulfillments->where('status', 'ready')->count(),
            'served' => $fulfillments->where('status', 'served')->count(),
            'delayed' => $fulfillments->filter(fn($f) => $f->status === 'in_progress' && $f->preparation_started_at->lt(now()->subMinutes(30)))->count(),
            'b2b' => $fulfillments->where('order_type', 'b2b')->count(),
            'b2c' => $fulfillments->where('order_type', 'b2c')->count(),
            'average_preparation_time' => $fulfillments->whereNotNull('duration_minutes')->avg('duration_minutes'),
            'average_rating' => $fulfillments->whereNotNull('customer_rating')->avg('customer_rating'),
        ];
    }

    public function getKDSData(int $restaurantId): array
    {
        $pending = OrderFulfillment::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['order', 'assignedChef', 'kitchenStation'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $pending->map(fn($f) => [
            'id' => $f->id,
            'order_id' => $f->order_id,
            'order_type' => $f->order_type,
            'status' => $f->status,
            'priority' => $f->priority,
            'items' => $f->items,
            'special_instructions' => $f->special_instructions,
            'allergies' => $f->allergies,
            'station' => $f->kitchenStation?->name,
            'chef' => $f->assignedChef?->name,
            'time_in_queue' => $f->created_at->diffInMinutes(now()),
            'preparation_time' => $f->duration_minutes,
        ])->toArray();
    }

    private function autoAssignToStation(OrderFulfillment $fulfillment): void
    {
        $station = \Modules\Restaurant\Domain\Entities\KitchenStation::where('restaurant_id', $fulfillment->restaurant_id)
            ->where('is_active', true)
            ->orderBy('queue_length', 'asc')
            ->first();

        if ($station) {
            $fulfillment->update(['kitchen_station_id' => $station->id]);
            $station->increment('queue_length');
        }
    }

    private function notifyWaiter(OrderFulfillment $fulfillment): void
    {
        // Send notification to waiter
        // Implementation depends on notification system
    }

    private function calculatePriority(string $orderType, \DateTime $orderCreatedAt): int
    {
        $basePriority = $orderType === 'b2b' ? 4 : 3;
        
        // Increase priority for old orders
        $minutesInQueue = now()->diffInMinutes($orderCreatedAt);
        if ($minutesInQueue > 20) {
            $basePriority = min(5, $basePriority + 1);
        }

        return $basePriority;
    }
}
