<?php declare(strict_types=1);

namespace App\Filament\Widgets;



use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

final class RevenueChartWidget extends ChartWidget
{
    public function __construct(
        private readonly CacheManager $cache,
    ) {}

    protected static ?string $heading = 'Выручка (30 дней)';
        protected static ?int $columnSpan = 2;
        protected static ?string $maxHeight = '300px';

        public function getType(): string
        {
            return 'line';
        }

        protected function getData(): array
        {
            $tenantId = $this->guard->user()->tenant_id;
            $data = $this->cache->remember(
                "revenue_chart:{$tenantId}",
                3600,
                function () {
                    return [
                        'labels' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                        'datasets' => [
                            [
                                'label' => 'Выручка (₽)',
                                'data' => [45000, 52000, 48000, 61000, 55000, 72000, 68000],
                                'borderColor' => '#2563eb',
                                'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                                'tension' => 0.4,
                                'fill' => true,
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
                        'display' => true,
                        'position' => 'top',
                    ],
                ],
                'scales' => [
                    'y' => [
                        'ticks' => [
                            'callback' => 'function(value) { return value.toLocaleString("ru-RU", {style: "currency", currency: "RUB", minimumFractionDigits: 0}); }',
                        ],
                    ],
                ],
            ];
        }
}
