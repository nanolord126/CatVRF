<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

/**
 * CustomerSegmentationWidget — распределение клиентов по сегментам
 */
final class CustomerSegmentationWidget extends ChartWidget
{
    protected static ?string $heading = 'Сегментация клиентов';
    protected static ?int $columnSpan = 1;
    protected static ?string $maxHeight = '300px';

    public function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $data = $this->cache->remember(
            "segments_chart:{$tenantId}",
            3600,
            function () {
                return [
                    'labels' => ['High-Value (125K+)', 'Medium-Value (10K-125K)', 'Low-Value (<10K)'],
                    'datasets' => [
                        [
                            'data' => [125, 350, 1500],
                            'backgroundColor' => [
                                '#10b981',  // green
                                '#f59e0b',  // amber
                                '#ef4444',  // red
                            ],
                            'borderColor' => '#ffffff',
                            'borderWidth' => 2,
                        ],
                    ],
                ];
            }
        );

        return $data;
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": " + context.parsed + " клиентов"; }',
                    ],
                ],
            ],
        ];
    }
}
