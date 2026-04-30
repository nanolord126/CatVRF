<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationStatisticsResource\Widgets;

use App\Domains\Shared\Notifications\Services\NotificationLogService;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\Cache;

final class DeliveryTrendWidget extends LineChartWidget
{
    protected static ?string $heading = 'Delivery Trend (30 Days)';

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $service = app(NotificationLogService::class);
        $trends = Cache::remember('delivery-trends', 60, fn() => $service->getTrends(30));

        return [
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => array_column($trends, 'total'),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Delivered',
                    'data' => array_column($trends, 'delivered'),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'Failed',
                    'data' => array_column($trends, 'failed'),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
            'labels' => array_column($trends, 'date'),
        ];
    }
}
