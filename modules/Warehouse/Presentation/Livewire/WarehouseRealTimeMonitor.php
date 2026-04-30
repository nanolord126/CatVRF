<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Livewire;

use Livewire\Attributes\Reactive;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Warehouse\Application\Services\WarehouseApplicationService;
use Modules\Warehouse\Application\Services\ZoneApplicationService;
use Modules\Warehouse\Application\Services\MovementApplicationService;
use Illuminate\Support\Facades\Cache;

/**
 * Warehouse Real-Time Monitor Livewire Component
 *
 * Provides real-time dashboard for warehouse operations
 * Separate from Inventory (stock counting process)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
class WarehouseRealTimeMonitor extends Component
{
    public array $warehouses = [];
    public array $zones = [];
    public array $recentMovements = [];
    public string $selectedWarehouseId = '';
    public int $refreshInterval = 30;
    public bool $autoRefresh = true;
    public array $stats = [
        'total_warehouses' => 0,
        'active_warehouses' => 0,
        'total_zones' => 0,
        'avg_utilization' => 0,
        'low_stock_alerts' => 0,
    ];

    public function mount(WarehouseApplicationService $warehouseService): void
    {
        $this->refreshData($warehouseService);
    }

    public function refreshData(WarehouseApplicationService $warehouseService): void
    {
        $tenantId = auth()->user()->tenant_id;

        // Get warehouses
        $warehouses = $warehouseService->getWarehousesByTenant($tenantId);
        $this->warehouses = array_map(fn($w) => $w->toArray(), $warehouses);

        // Calculate stats
        $this->stats['total_warehouses'] = count($this->warehouses);
        $this->stats['active_warehouses'] = count(array_filter($this->warehouses, fn($w) => $w['is_active']));

        $utilizations = array_column($this->warehouses, 'utilization_percentage');
        $this->stats['avg_utilization'] = count($utilizations) > 0
            ? round(array_sum($utilizations) / count($utilizations), 2)
            : 0;

        // Load zones for selected warehouse
        if ($this->selectedWarehouseId) {
            $this->loadZones();
        }
    }

    public function loadZones(ZoneApplicationService $zoneService = null): void
    {
        if (!$this->selectedWarehouseId || !$zoneService) {
            return;
        }

        $zones = $zoneService->getZonesByWarehouse($this->selectedWarehouseId);
        $this->zones = array_map(fn($z) => $z->toArray(), $zones);
        $this->stats['total_zones'] = count($this->zones);
    }

    public function loadRecentMovements(MovementApplicationService $movementService): void
    {
        if (!$this->selectedWarehouseId) {
            return;
        }

        $movements = $movementService->getMovementsByWarehouse($this->selectedWarehouseId);
        $this->recentMovements = array_map(fn($m) => $m->toArray(), array_slice($movements, 0, 10));
    }

    public function selectWarehouse(string $warehouseId): void
    {
        $this->selectedWarehouseId = $warehouseId;
    }

    #[On('echo:warehouse.{selectedWarehouseId},WarehouseUpdated')]
    public function onWarehouseUpdated(array $payload): void
    {
        $this->refreshData(app(WarehouseApplicationService::class));
    }

    #[On('echo:warehouse.{selectedWarehouseId},StockMovement')]
    public function onStockMovement(array $payload): void
    {
        $this->loadRecentMovements(app(MovementApplicationService::class));
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function render()
    {
        return view('warehouse::livewire.warehouse-real-time-monitor')
            ->layout('layouts.app');
    }
}
