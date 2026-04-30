<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationStatisticsResource\Widgets;

use App\Domains\Shared\Notifications\Services\NotificationLogService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

final class NotificationOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $service = app(NotificationLogService::class);
        $stats = Cache::remember('notification-overview', 60, fn() => $service->getStatistics());

        return [
            Stat::make('Total Sent', number_format($stats['total']))
                ->description('All notifications')
                ->descriptionIcon('heroicon-m-bell')
                ->color('primary'),
            Stat::make('Delivered', number_format($stats['delivered']))
                ->description($stats['delivery_rate'] . '% delivery rate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Failed', number_format($stats['failed']))
                ->description($stats['failure_rate'] . '% failure rate')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            Stat::make('Success Rate', $stats['success_rate'] . '%')
                ->description('Overall success')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
}
