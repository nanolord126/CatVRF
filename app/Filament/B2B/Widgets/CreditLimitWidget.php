<?php declare(strict_types=1);

namespace App\Filament\B2B\Widgets;


use Illuminate\Database\DatabaseManager;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * CreditLimitWidget — виджет кредитного лимита B2B-клиента.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Показывает: лимит / использовано / доступно / процент загрузки.
 * Данные из таблицы business_groups (tenant + business_group scoped).
 */
final class CreditLimitWidget extends StatsOverviewWidget
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $businessGroupId = session('active_business_group_id');

        if (!$businessGroupId) {
            return [
                Stat::make('Кредит', '–')
                    ->description('Бизнес-группа не выбрана')
                    ->color('gray'),
            ];
        }

        $group = $this->db->table('business_groups')
            ->where('id', $businessGroupId)
            ->first();

        if (!$group) {
            return [
                Stat::make('Кредит', '–')->color('gray'),
            ];
        }

        $limit     = (float) ($group->credit_limit ?? 0) / 100;
        $used      = (float) ($group->credit_used ?? 0) / 100;
        $available = max(0, $limit - $used);
        $pct       = $limit > 0 ? round(($used / $limit) * 100, 1) : 0;

        // Тренд — кредитная загрузка по дням (последние 7 дней)
        $trend = $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->whereIn('status', ['pending', 'processing'])
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw("DATE(created_at) as day, SUM(total_amount)/100 as total")
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total')
            ->map(static fn ($v) => (float) $v)
            ->toArray();

        // Задолженности: просроченные заказы (если есть due_date)
        $overdueCount = $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        return [
            Stat::make('Кредитный лимит', number_format($limit, 0, '.', ' ') . ' ₽')
                ->description('Tier: ' . strtoupper($group->b2b_tier ?? 'standard'))
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('primary'),

            Stat::make('Использовано', number_format($used, 0, '.', ' ') . ' ₽')
                ->description("{$pct}% от лимита")
                ->descriptionIcon(
                    $pct > 80
                        ? 'heroicon-o-exclamation-triangle'
                        : 'heroicon-o-arrow-trending-up'
                )
                ->color($pct > 80 ? 'danger' : ($pct > 60 ? 'warning' : 'success'))
                ->chart($trend ?: [0]),

            Stat::make('Доступно к использованию', number_format($available, 0, '.', ' ') . ' ₽')
                ->description(
                    $overdueCount > 0
                        ? "⚠ Просрочено заказов: {$overdueCount}"
                        : 'Просроченных нет'
                )
                ->descriptionIcon(
                    $overdueCount > 0
                        ? 'heroicon-o-clock'
                        : 'heroicon-o-check-circle'
                )
                ->color($overdueCount > 0 ? 'danger' : 'success'),
        ];
    }
}
