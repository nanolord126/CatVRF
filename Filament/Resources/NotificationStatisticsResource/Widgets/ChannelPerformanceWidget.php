<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationStatisticsResource\Widgets;

use App\Domains\Shared\Notifications\Services\NotificationLogService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

final class ChannelPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'Channel Performance';

    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $service = app(NotificationLogService::class);
        $stats = Cache::remember('channel-performance', 60, fn() => $service->getStatistics());
        $channelStats = $stats['by_channel'];

        return [
            'datasets' => [
                [
                    'label' => 'Delivered',
                    'data' => array_values($channelStats->map(fn($stat) => $stat['delivered'])->toArray()),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Failed',
                    'data' => array_values($channelStats->map(fn($stat) => $stat['failed'])->toArray()),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => array_keys($channelStats->toArray()),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
