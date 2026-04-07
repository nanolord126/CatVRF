<?php declare(strict_types=1);

namespace App\Filament\Widgets;



use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

final class ConversionChartWidget extends ChartWidget
{
    public function __construct(
        private readonly CacheManager $cache,
    ) {}

    protected static ?string $heading = 'Воронка конверсии';
        protected static ?int $columnSpan = 1;
        protected static ?string $maxHeight = '300px';

        public function getType(): string
        {
            return 'bar';
        }

        protected function getData(): array
        {
            $tenantId = $this->guard->user()->tenant_id;
            $data = $this->cache->remember(
                "conversion_chart:{$tenantId}",
                3600,
                function () {
                    return [
                        'labels' => ['Визиты', 'Добавили в корзину', 'Начали оплату', 'Завершили заказ'],
                        'datasets' => [
                            [
                                'label' => 'Пользователи',
                                'data' => [10000, 2500, 1250, 950],
                                'backgroundColor' => [
                                    '#3b82f6',
                                    '#8b5cf6',
                                    '#ec4899',
                                    '#10b981',
                                ],
                                'borderRadius' => 4,
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
                    'legend' => ['display' => false],
                ],
                'scales' => [
                    'y' => [
                        'ticks' => [
                            'callback' => 'function(value) { return value.toLocaleString("ru-RU"); }',
                        ],
                    ],
                ],
            ];
        }
}
