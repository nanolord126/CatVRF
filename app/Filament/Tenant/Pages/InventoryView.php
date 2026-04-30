<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\DatabaseManager;

/**
 * InventoryView — управление инвентарём в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: список товаров, остатки, категории, low-stock alerts.
 * Tenant-scoped: все данные фильтруются по tenant_id.
 */
final class InventoryView extends Page
{
    public array $inventory = [];
    public array $stats = [];
    public string $filter = 'all';
    public string $search = '';

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Инвентарь';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $slug = 'inventory';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.tenant.pages.inventory-view';

    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function mount(): void
    {
        $this->loadData();
    }

    public function refresh(): void
    {
        $this->loadData();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->loadData();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add')
                ->label('Добавить товар')
                ->icon('heroicon-o-plus')
                ->url('#'),
            Action::make('import')
                ->label('Импорт')
                ->icon('heroicon-o-arrow-up-tray')
                ->url('#'),
            Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-arrow-path')
                ->action('refresh'),
        ];
    }

    private function loadData(): void
    {
        $tenantId = tenant()?->id;
        if (!$tenantId) {
            return;
        }

        $query = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId);

        if ($this->filter === 'low_stock') {
            $query->whereColumn('quantity', '<=', 'min_quantity');
        } elseif ($this->filter === 'out_of_stock') {
            $query->where('quantity', 0);
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->inventory = $query
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $this->stats = [
            'total_items' => $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->count(),
            'low_stock' => $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->whereColumn('quantity', '<=', 'min_quantity')
                ->count(),
            'out_of_stock' => $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->where('quantity', 0)
                ->count(),
            'total_value' => (float) $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->selectRaw('SUM(quantity * cost_price) as value')
                ->first()->value / 100,
        ];
    }
}
