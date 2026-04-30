<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Application\Services\SellerAnalyticsService;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * GMV Trend Chart Widget
 *
 * Displays GMV trend over time using Filament Charts.
 * Compares current period with previous period.
 */
class GMVTrendChart extends ChartWidget
{
    protected static ?string $heading = 'GMV Trend';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? 1;

        if (!$sellerId) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $period = Period::last30Days();
        $trends = app(SellerAnalyticsService::class)
            ->getTrends($sellerId, $period, $tenantId);

        $gmvTrend = $trends[0] ?? null;
        $labels = [];
        $data = [];

        if ($gmvTrend) {
            foreach ($gmvTrend->dataPoints ?? [] as $point) {
                $labels[] = $point['date'];
                $data[] = $point['value'];
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'GMV',
                    'data' => $data,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toLocaleString(); }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    /**
     * Get the widget refresh interval in seconds.
     */
    protected static function getRefreshInterval(): ?int
    {
        return 300; // Refresh every 5 minutes
    }
}
