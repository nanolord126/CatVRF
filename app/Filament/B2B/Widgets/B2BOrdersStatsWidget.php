<?php declare(strict_types=1);

namespace App\Filament\B2B\Widgets;


use Illuminate\Database\DatabaseManager;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * B2BOrdersStatsWidget — статистика заказов B2B-клиента.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Показывает: активные заказы / оборот 30d / средний чек.
 * Данные из таблицы orders (business_group_id scoped).
 */
final class B2BOrdersStatsWidget extends StatsOverviewWidget
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $businessGroupId = session('active_business_group_id');

        if (!$businessGroupId) {
            return [
                Stat::make('Заказы', '–')->color('gray'),
            ];
        }

        $since30d = now()->subDays(30);
        $today    = now()->startOfDay();

        $activeOrders = $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->whereIn('status', ['pending', 'processing', 'shipped'])
            ->count();

        $completedOrders = $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->where('created_at', '>=', $since30d)
            ->where('status', 'completed')
            ->count();

        $gmv30d = (float) $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->where('created_at', '>=', $since30d)
            ->whereIn('status', ['completed', 'processing', 'shipped'])
            ->sum('total_amount') / 100;

        $avgCheck = $completedOrders > 0
            ? round($gmv30d / $completedOrders, 0)
            : 0;

        // Тренд — заказы по дням
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day    = now()->subDays($i)->startOfDay();
            $trend[] = $this->db->table('orders')
                ->where('business_group_id', $businessGroupId)
                ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                ->count();
        }

        // Тренд GMV
        $gmvTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day       = now()->subDays($i)->startOfDay();
            $gmvTrend[] = (float) $this->db->table('orders')
                ->where('business_group_id', $businessGroupId)
                ->whereIn('status', ['completed', 'processing', 'shipped'])
                ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                ->sum('total_amount') / 100;
        }

        return [
            Stat::make('Активных заказов', (string) $activeOrders)
                ->description('В обработке сейчас')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color($activeOrders > 0 ? 'info' : 'gray')
                ->chart($trend),

            Stat::make('Оборот (30 дней)', number_format($gmv30d, 0, '.', ' ') . ' ₽')
                ->description("{$completedOrders} выполненных заказов")
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart($gmvTrend),

            Stat::make('Средний чек', number_format($avgCheck, 0, '.', ' ') . ' ₽')
                ->description('За последние 30 дней')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('primary'),
        ];
    }
}
