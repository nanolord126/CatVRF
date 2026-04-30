<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\DatabaseManager;

/**
 * HRView — HR управление в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: найм, увольнение, отпуска, performance reviews.
 * Tenant-scoped: все данные фильтруются по tenant_id.
 */
final class HRView extends Page
{
    public array $employees = [];
    public array $stats = [];
    public string $tab = 'overview';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'HR';
    protected static ?string $navigationGroup = 'Business';
    protected static ?string $slug = 'hr';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.tenant.pages.hr-view';

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

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('hire')
                ->label('Нанять')
                ->icon('heroicon-o-user-plus')
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

        $this->employees = $this->db->table('staff')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $this->stats = [
            'total_employees' => count($this->employees),
            'on_vacation' => $this->db->table('staff_leaves')
                ->where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->where('end_date', '>=', now())
                ->count(),
            'pending_requests' => $this->db->table('staff_leaves')
                ->where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->count(),
            'open_positions' => $this->db->table('job_openings')
                ->where('tenant_id', $tenantId)
                ->where('status', 'open')
                ->count(),
        ];
    }
}
