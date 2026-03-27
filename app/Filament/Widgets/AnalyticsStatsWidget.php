<?php

declare(strict_types=1);


namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * AnalyticsStatsWidget — статистика KPI на главной
 */
final class AnalyticsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        $stats = Cache::remember(
            "stats_overview:{$tenantId}",
            1800,
            function () {
                return [
                    Stat::make('Выручка (30д)', '₽ 250,000')
                        ->description('↑ 12.5% vs прошлый месяц')
                        ->descriptionIcon('heroicon-m-arrow-trending-up')
                        ->color('success')
                        ->chart([7, 2, 10, 3, 15, 4, 17]),
                    
                    Stat::make('Заказов (30д)', '125')
                        ->description('↑ 8.3% vs прошлый месяц')
                        ->descriptionIcon('heroicon-m-arrow-trending-up')
                        ->color('info')
                        ->chart([3, 12, 5, 4, 12, 8, 7]),
                    
                    Stat::make('Средний чек', '₽ 2,000')
                        ->description('↓ 2.1% vs прошлый месяц')
                        ->descriptionIcon('heroicon-m-arrow-trending-down')
                        ->color('warning'),
                    
                    Stat::make('Конверсия', '4.5%')
                        ->description('↑ 0.8% vs прошлый месяц')
                        ->descriptionIcon('heroicon-m-arrow-trending-up')
                        ->color('success'),
                ];
            }
        );

        return $stats;
    }
}
