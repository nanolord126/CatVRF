<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\DatabaseManager;

/**
 * WarehouseView — управление складами в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: список складов, ёмкость, перемещения, приёмка.
 * Tenant-scoped: все данные фильтруются по tenant_id.
 */
final class WarehouseView extends Page
{
    public array $warehouses = [];
    public array $stats = [];
    public string $filter = 'all';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Склады';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $slug = 'warehouses';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.tenant.pages.warehouse-view';

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add')
                ->label('Добавить склад')
                ->icon('heroicon-o-plus')
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

        $this->warehouses = $this->db->table('warehouses')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $this->stats = [
            'total_warehouses' => count($this->warehouses),
            'total_capacity' => (float) $this->db->table('warehouses')
                ->where('tenant_id', $tenantId)
                ->sum('capacity'),
            'used_capacity' => (float) $this->db->table('warehouses')
                ->where('tenant_id', $tenantId)
                ->sum('used_capacity'),
            'pending_transfers' => $this->db->table('warehouse_transfers')
                ->where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->count(),
        ];
    }
}
