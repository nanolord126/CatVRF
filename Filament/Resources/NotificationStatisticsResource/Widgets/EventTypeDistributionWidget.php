<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationStatisticsResource\Widgets;

use App\Domains\Shared\Notifications\Services\NotificationLogService;
use Filament\Widgets\PieChartWidget;
use Illuminate\Support\Facades\Cache;

final class EventTypeDistributionWidget extends PieChartWidget
{
    protected static ?string $heading = 'Event Type Distribution';

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $service = app(NotificationLogService::class);
        $stats = Cache::remember('event-distribution', 60, fn() => $service->getStatistics());
        $eventStats = $stats['by_event'];

        return [
            'datasets' => [
                [
                    'data' => array_column($eventStats, 'count'),
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(234, 179, 8)',
                        'rgb(239, 68, 68)',
                        'rgb(168, 85, 247)',
                        'rgb(6, 182, 212)',
                    ],
                ],
            ],
            'labels' => array_column($eventStats, 'event_type'),
        ];
    }
}
