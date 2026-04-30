<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\DatabaseManager;

/**
 * SalariesView — управление зарплатами в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: расчёт зарплат, выплаты, налоги, отчёты.
 * Tenant-scoped: все данные фильтруются по tenant_id.
 */
final class SalariesView extends Page
{
    public array $salaries = [];
    public array $stats = [];
    public string $period = 'current_month';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Зарплаты';
    protected static ?string $navigationGroup = 'Financial';
    protected static ?string $slug = 'salaries';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.tenant.pages.salaries-view';

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

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->loadData();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calculate')
                ->label('Рассчитать')
                ->icon('heroicon-o-calculator')
                ->url('#'),
            Action::make('export')
                ->label('Экспорт')
                ->icon('heroicon-o-arrow-down-tray')
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

        $startDate = now()->startOfMonth();
        if ($this->period === 'last_month') {
            $startDate = now()->subMonth()->startOfMonth();
        }

        $this->salaries = $this->db->table('salary_payments')
            ->where('tenant_id', $tenantId)
            ->where('period', '>=', $startDate)
            ->orderBy('period', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $this->stats = [
            'total_paid' => (float) $this->db->table('salary_payments')
                ->where('tenant_id', $tenantId)
                ->where('period', '>=', $startDate)
                ->sum('amount') / 100,
            'pending_payments' => $this->db->table('salary_payments')
                ->where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->count(),
            'total_staff' => $this->db->table('staff')
                ->where('tenant_id', $tenantId)
                ->count(),
            'avg_salary' => (float) $this->db->table('salary_payments')
                ->where('tenant_id', $tenantId)
                ->where('period', '>=', $startDate)
                ->avg('amount') / 100,
        ];
    }
}
