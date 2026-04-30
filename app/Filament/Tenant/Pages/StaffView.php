<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Domains\Staff\Domain\Entities\Staff;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

/**
 * StaffView — управление персоналом в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: список сотрудников, роли, расписание, KPI,
 * статистика эффективности, выручки, оценки.
 * Tenant-scoped: все данные фильтруются по tenant_id.
 */
final class StaffView extends Page
{
    public array $staff = [];
    public array $stats = [];
    public array $topPerformers = [];
    public string $filter = 'active';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Персонал';
    protected static ?string $navigationGroup = 'Business';
    protected static ?string $slug = 'staff';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.tenant.pages.staff-view';

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

    public function setSort(string $sortBy): void
    {
        if ($this->sortBy === $sortBy) {
            $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortDirection = 'desc';
        }
        $this->loadData();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Добавить сотрудника')
                ->icon('heroicon-o-plus')
                ->url(route('filament.tenant.resources.staff.create')),
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

        $query = Staff::where('tenant_id', $tenantId);

        // Apply filter
        if ($this->filter === 'active') {
            $query->active();
        } elseif ($this->filter === 'inactive') {
            $query->inactive();
        } elseif ($this->filter === 'on_vacation') {
            $query->onVacation();
        } elseif ($this->filter === 'archived') {
            $query->archived();
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $this->staff = $query
            ->with(['user', 'manager'])
            ->limit(50)
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'uuid' => $staff->uuid,
                    'full_name' => $staff->full_name,
                    'photo_url' => $staff->photo_url,
                    'position' => $staff->position,
                    'department' => $staff->department,
                    'role' => $staff->role,
                    'email' => $staff->email,
                    'phone' => $staff->phone,
                    'status' => $staff->status,
                    'total_orders_processed' => $staff->total_orders_processed,
                    'total_revenue_generated' => $staff->total_revenue_in_rubles,
                    'customer_satisfaction_score' => $staff->customer_satisfaction_score,
                    'quality_score' => $staff->quality_score,
                    'efficiency_score' => $staff->calculateEfficiencyScore(),
                    'hired_at' => $staff->hired_at?->format('d.m.Y'),
                ];
            })
            ->toArray();

        // Calculate stats
        $allStaff = Staff::where('tenant_id', $tenantId)->get();

        $this->stats = [
            'total' => $allStaff->count(),
            'active' => $allStaff->where('status', 'active')->count(),
            'inactive' => $allStaff->where('status', 'inactive')->count(),
            'on_vacation' => $allStaff->where('status', 'on_vacation')->count(),
            'archived' => $allStaff->where('status', 'archived')->count(),
            'total_revenue' => $allStaff->sum('total_revenue_in_rubles'),
            'avg_satisfaction' => $allStaff->avg('customer_satisfaction_score') ?? 0,
            'avg_efficiency' => $allStaff->map(fn($s) => $s->calculateEfficiencyScore())->avg() ?? 0,
            'total_orders' => $allStaff->sum('total_orders_processed'),
        ];

        // Top performers
        $this->topPerformers = Staff::where('tenant_id', $tenantId)
            ->active()
            ->topPerformers(5)
            ->get()
            ->map(function ($staff) {
                return [
                    'full_name' => $staff->full_name,
                    'photo_url' => $staff->photo_url,
                    'position' => $staff->position,
                    'total_revenue' => $staff->total_revenue_in_rubles,
                    'efficiency_score' => $staff->calculateEfficiencyScore(),
                    'customer_satisfaction' => $staff->customer_satisfaction_score,
                ];
            })
            ->toArray();
    }
}
