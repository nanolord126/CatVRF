<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Http\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\KitchenStationType;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;
use Modules\Restaurant\Domain\Enums\OrderPriority;
use Modules\Restaurant\Domain\Repositories\OrderKitchenStatusRepositoryInterface;
use Modules\Restaurant\Infrastructure\Repositories\OrderKitchenStatusRepository;

/**
 * KDS Display для кухонной станции
 * Отображает заказы для конкретной станции (холодный/горячий цех, бар и т.д.)
 * с реал-тайм обновлениями через WebSocket
 */
final class KitchenDisplayStation extends Component
{
    #[Locked]
    public int $stationId;

    #[Locked]
    public KitchenStationType $stationType;

    public array $orders = [];

    public string $filter = 'active'; // active, all, completed

    public int $refreshInterval = 5; // секунды

    public function mount(int $stationId, KitchenStationType $stationType): void
    {
        $this->stationId = $stationId;
        $this->stationType = $stationType;
        $this->loadOrders();
    }

    public function loadOrders(): void
    {
        $repository = app(OrderKitchenStatusRepositoryInterface::class);
        
        $statuses = match ($this->filter) {
            'active' => [OrderKitchenStatusEnum::PENDING, OrderKitchenStatusEnum::IN_PROGRESS],
            'completed' => [OrderKitchenStatusEnum::READY],
            default => null,
        };

        $orders = $repository->getByStation($this->stationId, $statuses);
        
        $this->orders = collect($orders)
            ->map(fn (OrderKitchenStatus $order) => $this->formatOrder($order))
            ->sortBy([
                fn ($a, $b) => $b['priority']['level'] <=> $a['priority']['level'], // Сначала по приоритету
                fn ($a, $b) => $a['created_at'] <=> $b['created_at'], // Потом по времени
            ])
            ->values()
            ->toArray();
    }

    /**
     * Обновить статус заказа
     */
    public function updateOrderStatus(int $orderId, string $status): void
    {
        $repository = app(OrderKitchenStatusRepositoryInterface::class);
        $orderStatus = $repository->findById($orderId);

        if ($orderStatus === null) {
            return;
        }

        $newStatus = OrderKitchenStatusEnum::from($status);
        $updatedOrder = $orderStatus->withStatus($newStatus);
        
        $repository->save($updatedOrder);

        // Транслируем событие для всех клиентов
        event(new \Modules\Restaurant\Domain\Events\OrderStatusUpdated(
            $updatedOrder,
            $orderStatus->status->value
        ));

        $this->loadOrders();
    }

    /**
     * Отметить заказ как проблему
     */
    public function reportProblem(int $orderId, string $comment): void
    {
        $repository = app(OrderKitchenStatusRepositoryInterface::class);
        $orderStatus = $repository->findById($orderId);

        if ($orderStatus === null) {
            return;
        }

        $updatedOrder = $orderStatus->withProblem($comment);
        $repository->save($updatedOrder);

        event(new \Modules\Restaurant\Domain\Events\OrderProblemReported($updatedOrder));

        $this->loadOrders();
    }

    /**
     * Слушатель события обновления статуса заказа (WebSocket)
     */
    #[On('echo:kitchen.{stationId},OrderStatusUpdated')]
    public function onOrderStatusUpdated(array $payload): void
    {
        $this->loadOrders();
    }

    /**
     * Слушатель события нового заказа (WebSocket)
     */
    #[On('echo:kitchen.{stationId},OrderSentToKitchen')]
    public function onOrderSentToKitchen(array $payload): void
    {
        $this->loadOrders();
    }

    /**
     * Слушатель события просрочки заказа (WebSocket)
     */
    #[On('echo:kitchen.{stationId},OrderOverdue')]
    public function onOrderOverdue(array $payload): void
    {
        $this->loadOrders();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->loadOrders();
    }

    private function formatOrder(OrderKitchenStatus $order): array
    {
        $orderModel = \App\Models\Order::with('items')->find($order->orderId);
        
        return [
            'id' => $order->id,
            'order_id' => $order->orderId,
            'order_uuid' => $orderModel?->uuid,
            'status' => $order->status->value,
            'status_label' => $order->status->getLabel(),
            'priority' => [
                'value' => $order->priority->value,
                'label' => $order->priority->label(),
                'level' => $order->priority->level(),
                'color' => $order->priority->color(),
            ],
            'estimated_minutes' => $order->estimatedPreparationTime->minutes,
            'elapsed_minutes' => $order->getElapsedMinutes(),
            'time_remaining' => $order->getTimeRemaining(),
            'progress' => $order->getProgress(),
            'is_overdue' => $order->isOverdue(),
            'is_vip' => $order->isVip,
            'is_from_marketplace' => $order->isFromMarketplace,
            'started_at' => $order->startedAt?->toIso8601String(),
            'completed_at' => $order->completedAt?->toIso8601String(),
            'created_at' => $order->createdAt->toIso8601String(),
            'updated_at' => $order->updatedAt->toIso8601String(),
            'problem_comment' => $order->problemComment,
            'notes' => $order->notes,
            'items' => $orderModel?->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'options' => $item->options,
            ])->toArray() ?? [],
        ];
    }

    public function render()
    {
        return view('restaurant::livewire.kitchen-display-station')
            ->layout('layouts.kiosk');
    }
}
