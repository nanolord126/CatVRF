<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Widget: Real-Time Orders Statistics
 * Обновляется каждые 5 секунд через WebSocket
 * 
 * @package App\Filament\Widgets
 */
final class RealtimeOrderStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public function getStats(): array
    {
        $tenantId = filament()->getTenant()?->id;

        try {
            // Get real-time stats from cache (updated via events)
            $todayOrders = Cache::get("stats:orders:today:{$tenantId}", 0);
            $todayRevenue = Cache::get("stats:revenue:today:{$tenantId}", 0);
            $pendingOrders = Cache::get("stats:orders:pending:{$tenantId}", 0);

            // Trending data
            $yesterdayOrders = Cache::get("stats:orders:yesterday:{$tenantId}", 1);
            $orderTrend = $yesterdayOrders > 0 
                ? (($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100 
                : 0;

            return [
                Stat::make('Заказы сегодня', $todayOrders)
                    ->description(sprintf('%s%% чем вчера', 
                        $orderTrend > 0 ? '+' . round($orderTrend, 1) : round($orderTrend, 1)
                    ))
                    ->descriptionIcon($orderTrend > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($orderTrend > 0 ? 'success' : 'danger')
                    ->icon('heroicon-o-shopping-cart')
                    ->url(route('filament.admin.resources.orders.index')),

                Stat::make('Доход сегодня', number_format($todayRevenue / 100, 2, ',', ' ') . ' ₽')
                    ->icon('heroicon-o-banknotes')
                    ->color('success'),

                Stat::make('В ожидании', $pendingOrders)
                    ->description('Требуют внимания')
                    ->descriptionIcon('heroicon-m-clock')
                    ->icon('heroicon-o-clock')
                    ->color($pendingOrders > 0 ? 'warning' : 'info')
                    ->url(route('filament.admin.resources.orders.index', 
                        ['tableFilters[status][value]' => 'pending']
                    )),
            ];
        } catch (\Throwable $e) {
            return [
                Stat::make('Ошибка', 'Не удалось загрузить статистику')
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }
    }
}
