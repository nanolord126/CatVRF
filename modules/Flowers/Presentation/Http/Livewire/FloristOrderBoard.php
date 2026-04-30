<?php

declare(strict_types=1);

namespace Modules\Flowers\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Flowers\Application\Services\OrderService;
use Modules\Flowers\Domain\Repositories\OrderRepositoryInterface;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Illuminate\Support\Facades\Auth;

final class FloristOrderBoard extends Component
{
    use WithPagination;

    public int $venueId;
    public ?int $floristId = null;
    public string $filter = 'all'; // all, pending, in_assembly, ready
    public string $search = '';

    protected $queryString = ['filter', 'search'];

    public function mount(): void
    {
        $this->floristId = Auth::id();
    }

    public function getOrdersProperty()
    {
        $orders = match ($this->filter) {
            'pending' => $this->orderRepository->getPendingOrders($this->venueId),
            'in_assembly' => $this->orderRepository->getInAssemblyOrders($this->venueId),
            'ready' => $this->orderRepository->getReadyForDeliveryOrders($this->venueId),
            default => $this->orderRepository->getByVenue($this->venueId, []),
        };

        if ($this->search) {
            $orders = array_filter($orders, fn ($order) => 
                str_contains(strtolower($order->orderNumber), strtolower($this->search)) ||
                str_contains(strtolower($order->recipientName), strtolower($this->search))
            );
        }

        return array_values($orders);
    }

    public function startAssembly(int $orderId): void
    {
        $this->orderService->updateOrderStatus($orderId, OrderStatus::IN_ASSEMBLY);
        $this->dispatch('order-updated');
    }

    public function completeAssembly(int $orderId): void
    {
        $this->orderService->updateOrderStatus($orderId, OrderStatus::ASSEMBLED);
        $this->dispatch('order-updated');
    }

    public function markAsQualityChecked(int $orderId): void
    {
        $this->orderService->updateOrderStatus($orderId, OrderStatus::QUALITY_CHECKED);
        $this->dispatch('order-updated');
    }

    public function markAsReady(int $orderId): void
    {
        $this->orderService->updateOrderStatus($orderId, OrderStatus::READY_FOR_DELIVERY);
        $this->dispatch('order-updated');
    }

    public function render()
    {
        return view('flowers::livewire.florist-order-board', [
            'orders' => $this->orders,
        ]);
    }
}
