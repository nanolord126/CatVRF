<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

/**
 * Widget: Real-Time Revenue Chart
 * Обновляется каждые 10 секунд
 * 
 * @package App\Filament\Widgets
 */
final class RealtimeRevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Доход в реальном времени';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'md';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $tenantId = filament()->getTenant()?->id;
        
        // Get hourly revenue for last 24 hours
        $labels = [];
        $data = [];

        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i);
            $labels[] = $hour->format('H:i');
            $key = "stats:revenue:hour:{$tenantId}:{$hour->format('Y-m-d-H')}";
            $data[] = Cache::get($key, 0) / 100; // Convert to rubles
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Доход (₽)',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
