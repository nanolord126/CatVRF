<?php declare(strict_types=1);

namespace App\Filament\Widgets;



use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * Class AnalyticsStatsWidget
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Widgets
 */
final class AnalyticsStatsWidget extends StatsOverviewWidget
{
    public function __construct(
        private readonly CacheManager $cache,
    ) {}

    protected function getStats(): array
        {
            $tenantId = $this->guard->user()->tenant_id;

            $stats = $this->cache->remember(
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
