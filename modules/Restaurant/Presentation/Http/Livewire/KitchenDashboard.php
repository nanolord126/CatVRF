<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Http\Livewire;

use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Restaurant\Application\Services\KitchenService;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;

final class KitchenDashboard extends Component
{
    use WithPagination;

    public int $selectedStationId = 0;
    public string $filterStatus = 'all';
    public string $filterPriority = 'all';
    public bool $autoRefresh = true;
    public int $refreshInterval = 5;

    public function mount(KitchenService $kitchenService): void
    {
        $this->selectedStationId = 0;
    }

    public function render(KitchenService $kitchenService)
    {
        $tenantId = tenant()?->id ?? 0;
        
        $stations = $kitchenService->getActiveStations($tenantId);
        
        if ($this->selectedStationId === 0 && $stations->isNotEmpty()) {
            $this->selectedStationId = $stations->first()->id;
        }

        $orders = $this->selectedStationId > 0
            ? $kitchenService->getActiveOrdersForStation($this->selectedStationId)
            : collect();

        $orders = $this->filterOrders($orders);
        $stats = $kitchenService->getKitchenStats($tenantId);

        return view('restaurant::livewire.kitchen-dashboard', [
            'stations' => $stations,
            'orders' => $orders,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    private function filterOrders(Collection $orders): Collection
    {
        if ($this->filterStatus !== 'all') {
            $status = OrderKitchenStatusEnum::from($this->filterStatus);
            $orders = $orders->filter(fn ($o) => $o->status === $status);
        }

        if ($this->filterPriority !== 'all') {
            $orders = $orders->filter(fn ($o) => $o->priority->value === $this->filterPriority);
        }

        return $orders;
    }

    #[On('echo:kitchen.{stationId},order.sent')]
    #[On('echo:kitchen.{stationId},order.updated')]
    #[On('echo:kitchen.{stationId},order.priority_changed')]
    public function refreshOrders(): void
    {
        $this->render(app(KitchenService::class));
    }

    public function selectStation(int $stationId): void
    {
        $this->selectedStationId = $stationId;
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
    }
}
