<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Http\Livewire;

use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Restaurant\Application\Services\KitchenService;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

final class StationView extends Component
{
    public int $stationId;
    public Collection $orders;
    public array $stats = [];

    public function mount(int $stationId, KitchenService $kitchenService): void
    {
        $this->stationId = $stationId;
        $this->loadData($kitchenService);
    }

    public function render(KitchenService $kitchenService)
    {
        $station = $kitchenService->getStation($this->stationId);
        
        return view('restaurant::livewire.station-view', [
            'station' => $station,
            'orders' => $this->orders,
            'stats' => $this->stats,
        ]);
    }

    private function loadData(KitchenService $kitchenService): void
    {
        $this->orders = $kitchenService->getActiveOrdersForStation($this->stationId);
        
        $this->stats = [
            'total' => $this->orders->count(),
            'pending' => $this->orders->filter(fn ($o) => $o->status->value === 'pending')->count(),
            'in_progress' => $this->orders->filter(fn ($o) => $o->status->value === 'in_progress')->count(),
            'ready' => $this->orders->filter(fn ($o) => $o->status->value === 'ready')->count(),
            'overdue' => $this->orders->filter(fn ($o) => $o->isOverdue())->count(),
        ];
    }

    #[On('echo:kitchen.{stationId},order.sent')]
    #[On('echo:kitchen.{stationId},order.updated')]
    #[On('echo:kitchen.{stationId},order.priority_changed')]
    public function refreshOrders(): void
    {
        $this->loadData(app(KitchenService::class));
    }

    public function startOrder(int $orderStatusId): void
    {
        $kitchenService = app(KitchenService::class);
        $kitchenService->startOrder($orderStatusId);
        $this->loadData($kitchenService);
    }

    public function completeOrder(int $orderStatusId): void
    {
        $kitchenService = app(KitchenService::class);
        $kitchenService->completeOrder($orderStatusId);
        $this->loadData($kitchenService);
    }

    public function serveOrder(int $orderStatusId): void
    {
        $kitchenService = app(KitchenService::class);
        $kitchenService->serveOrder($orderStatusId);
        $this->loadData($kitchenService);
    }

    public function cancelOrder(int $orderStatusId): void
    {
        $kitchenService = app(KitchenService::class);
        $kitchenService->cancelOrder($orderStatusId);
        $this->loadData($kitchenService);
    }

    public function reportProblem(int $orderStatusId, string $comment): void
    {
        $kitchenService = app(KitchenService::class);
        $kitchenService->reportProblem($orderStatusId, $comment);
        $this->loadData($kitchenService);
    }

    public function setPriority(int $orderStatusId, string $priority): void
    {
        $kitchenService = app(KitchenService::class);
        $priorityEnum = \Modules\Restaurant\Domain\Enums\OrderPriority::from($priority);
        $kitchenService->updateOrderPriority($orderStatusId, $priorityEnum);
        $this->loadData($kitchenService);
    }
}
