<?php declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;


use Illuminate\Database\DatabaseManager;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * OrdersStatsWidget — статистика заказов tenant'а.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Показывает: заказы сегодня / за неделю / конверсию.
 * Данные из таблицы orders (tenant-scoped).
 */
final class OrdersStatsWidget extends StatsOverviewWidget
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $tenantId = tenant()?->id;

        if (!$tenantId) {
            return [
                Stat::make('Заказы', '–')->color('gray'),
            ];
        }

        $today  = now()->startOfDay();
        $week   = now()->subDays(7);
        $month  = now()->subDays(30);

        $ordersToday = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $today)
            ->count();

        $ordersWeek = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $week)
            ->count();

        $completedMonth = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $month)
            ->where('status', 'completed')
            ->count();

        $totalMonth = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $month)
            ->count();

        $conversion = $totalMonth > 0
            ? round(($completedMonth / $totalMonth) * 100, 1)
            : 0.0;

        // Тренд — заказы по дням за последние 7 дней
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $trend[] = $this->db->table('orders')
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                ->count();
        }

        // Trend for completed
        $completedTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $completedTrend[] = $this->db->table('orders')
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                ->count();
        }

        return [
            Stat::make('Заказы сегодня', (string) $ordersToday)
                ->description('Оформлено за текущие сутки')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('primary')
                ->chart($trend),

            Stat::make('Заказы за 7 дней', (string) $ordersWeek)
                ->description('Всего за неделю')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info')
                ->chart($trend),

            Stat::make('Конверсия (30 дней)', $conversion . '%')
                ->description("{$completedMonth} из {$totalMonth} выполнено")
                ->descriptionIcon($conversion >= 70 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($conversion >= 70 ? 'success' : ($conversion >= 40 ? 'warning' : 'danger'))
                ->chart($completedTrend),
        ];
    }
}
