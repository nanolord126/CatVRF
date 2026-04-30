<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use Illuminate\Http\Client\Factory as HttpClientFactory;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Livewire\Component;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

final class TimeSeriesChartComponent extends Component
{
    // Параметры
    private readonly string $vertical = 'beauty';

    private readonly string $chartType = 'line'; // line|bar

    private readonly string $heatmapType = 'geo'; // geo|click

    private readonly string $aggregation = 'daily'; // hourly|daily|weekly

    private readonly string $metric = 'event_count'; // event_count|unique_users|unique_sessions

    private readonly string $pageUrl = '';

    private readonly string $fromDate = '';

    private readonly string $toDate = '';

    // Режимы
    private readonly bool $isComparison = false;

    private readonly string $period1From = '';

    private readonly string $period1To = '';

    private readonly string $period2From = '';

    private readonly string $period2To = '';

    // Кастомные метрики
    private readonly bool $isCustomMetric = false;

    private readonly string $customMetric = 'event_intensity';

    // Состояние
    private readonly array $chartData = [];

    private readonly array $chartConfig = [];

    private readonly bool $isLoading = false;

    private readonly string $errorMessage = '';

    private readonly string $correlationId = '';

    // Опции
    private readonly bool $showLegend = true;

    private readonly bool $showGrid = true;

    private readonly bool $showTooltip = true;

    private readonly bool $enableExport = true;

    public function __construct(private readonly HttpClientFactory $http,
        private readonly ViewFactory $viewFactory,
        private readonly LoggerInterface $logger,
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,) {}

    public function mount(): void
    {
        // Установить дефолтные даты
        if (! $this->fromDate) {
            $this->fromDate = CarbonImmutable::now()->subDays(30)->format('Y-m-d');
        }
        if (! $this->toDate) {
            $this->toDate = CarbonImmutable::now()->format('Y-m-d');
        }

        // Сгенерировать correlation ID
        $this->correlationId = Str::uuid()->toString();

        // Загрузить начальные данные
        $this->loadChartData();
    }

    /**
     * Загрузить данные из API
     */
    public function loadChartData(): void
    {
        $this->isLoading = true;
        $this->errorMessage = '';

        try {
            if ($this->isCustomMetric) {
                $this->loadCustomMetricData();
            } elseif ($this->isComparison) {
                $this->loadComparisonData();
            } else {
                $this->loadTimeSeriesData();
            }

            $this->buildChartConfig();
            $this->dispatch('chart-data-loaded');

        } catch (\Exception $e) {
            $this->errorMessage = 'Не удалось загрузить данные: '.$e->getMessage();
            $this->logger->channel('error')->error('Chart data loading failed', [
                'correlation_id' => $this->correlationId,
                'heatmap_type' => $this->heatmapType,
                'message' => $e->getMessage(),
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Слушатель WebSocket: перезагрузить данные
     */
    #[On('reload-chart-data')]
    public function reloadChartData(): void
    {
        $this->logger->channel('analytics')->$this->logger->info('Reloading chart data via WebSocket', [
            'correlation_id' => $this->correlationId,
            'heatmap_type' => $this->heatmapType,
            'vertical' => $this->vertical,
        ]);

        $this->loadChartData();
    }

    /**
     * Обновить агрегацию
     */
    public function updateAggregation(string $aggregation): void
    {
        $this->aggregation = $aggregation;
        $this->loadChartData();
    }

    /**
     * Обновить метрику
     */
    public function updateMetric(string $metric): void
    {
        $this->metric = $metric;
        $this->loadChartData();
    }

    /**
     * Обновить тип графика
     */
    public function updateChartType(string $type): void
    {
        $this->chartType = $type;
        $this->buildChartConfig();
    }

    /**
     * Обновить диапазон дат
     */
    public function updateDateRange(string $from, string $to): void
    {
        $this->fromDate = $from;
        $this->toDate = $to;
        $this->loadChartData();
    }

    /**
     * Переключить режим сравнения
     */
    public function toggleComparisonMode(): void
    {
        $this->isComparison = ! $this->isComparison;
        if ($this->isComparison) {
            // Инициализировать дефолтные периоды
            $this->period1From = CarbonImmutable::now()->subDays(30)->format('Y-m-d');
            $this->period1To = CarbonImmutable::now()->subDays(15)->format('Y-m-d');
            $this->period2From = CarbonImmutable::now()->subDays(15)->format('Y-m-d');
            $this->period2To = CarbonImmutable::now()->format('Y-m-d');
        }
        $this->loadChartData();
    }

    /**
     * Переключить кастомные метрики
     */
    public function toggleCustomMetric(string $metric = ''): void
    {
        $this->isCustomMetric = ! $this->isCustomMetric;
        if ($metric) {
            $this->customMetric = $metric;
        }
        $this->loadChartData();
    }

    /**
     * Экспортировать в PNG
     */
    public function exportPng(): void
    {
        $this->logger->channel('audit')->$this->logger->info('PNG export initiated', [
            'correlation_id' => $this->correlationId,
            'heatmap_type' => $this->heatmapType,
        ]);
        // Браузер сохранит PNG через JavaScript
        $this->dispatch('export-chart-png');
    }

    /**
     * Экспортировать в PDF
     */
    public function exportPdf(): void
    {
        $this->logger->channel('audit')->$this->logger->info('PDF export initiated', [
            'correlation_id' => $this->correlationId,
            'heatmap_type' => $this->heatmapType,
        ]);
        // Браузер сохранит PDF через JavaScript
        $this->dispatch('export-chart-pdf', chartData: $this->chartData);
    }

    /**
     * Polling метод - автообновление каждые 30 секунд
     * Вызывается через wire:poll.30000ms
     */
    public function pollChartData(): void
    {
        $this->logger->channel('analytics')->debug('Polling chart data (30s interval)', [
            'correlation_id' => $this->correlationId,
            'heatmap_type' => $this->heatmapType,
            'vertical' => $this->vertical,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);

        try {
            // Если компонент загружается, пропустить polling
            if ($this->isLoading) {
                return;
            }

            // Загрузить новые данные
            $this->loadChartData();

            // Логирование успеха
            $this->logger->channel('analytics')->debug('Poll completed successfully', [
                'correlation_id' => $this->correlationId,
                'data_points' => count($this->chartData['datasets'][0]['data'] ?? []),
            ]);

        } catch (\Exception $e) {
            // Логирование ошибки, но не прерывать polling
            $this->logger->channel('analytics')->warning('Poll error (non-critical)', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.analytics.time-series-chart-component', [
            'availableAggregations' => ['hourly', 'daily', 'weekly'],
            'availableMetrics' => ['event_count', 'unique_users', 'unique_sessions'],
            'availableCustomMetrics' => [
                'event_intensity' => 'Интенсивность событий',
                'engagement_score' => 'Оценка вовлечённости',
                'growth_rate' => 'Темп роста',
                'hotspot_concentration' => 'Концентрация горячих точек',
                'user_retention' => 'Удержание пользователей',
                'click_density' => 'Плотность кликов',
                'interaction_score' => 'Оценка взаимодействия',
                'user_engagement' => 'Вовлечённость пользователя',
                'click_conversion' => 'Конверсия по кликам',
            ],
            'chartTypes' => ['line', 'bar'],
        ]);
    }

    /**
     * Загрузить времеsseries данные
     */
    private function loadTimeSeriesData(): void
    {
        $endpoint = $this->heatmapType === 'geo'
            ? '/api/analytics/heatmaps/timeseries/geo'
            : '/api/analytics/heatmaps/timeseries/click';

        $params = [
            'vertical' => $this->vertical,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'aggregation' => $this->aggregation,
        ];

        if ($this->heatmapType === 'geo') {
            $params['metric'] = $this->metric;
        } else {
            $params['page_url'] = $this->pageUrl;
        }

        $response = $this->http->withHeader('X-Correlation-ID', $this->correlationId)
            ->timeout(30)
            ->get($this->config->get('app.url').$endpoint, $params);

        if ($response->failed()) {
            throw new \RuntimeException("API error: {$response->status()}");
        }

        $data = $response->json('data');
        $this->chartData = $this->formatTimeSeriesData($data);
    }

    /**
     * Загрузить comparison данные
     */
    private function loadComparisonData(): void
    {
        $endpoint = $this->heatmapType === 'geo'
            ? '/api/analytics/heatmaps/compare/geo'
            : '/api/analytics/heatmaps/compare/click';

        $params = [
            'vertical' => $this->vertical,
            'period1_from' => $this->period1From,
            'period1_to' => $this->period1To,
            'period2_from' => $this->period2From,
            'period2_to' => $this->period2To,
        ];

        if ($this->heatmapType === 'geo') {
            $params['metric'] = $this->metric;
        } else {
            $params['page_url'] = $this->pageUrl;
        }

        $response = $this->http->withHeader('X-Correlation-ID', $this->correlationId)
            ->timeout(30)
            ->get($this->config->get('app.url').$endpoint, $params);

        if ($response->failed()) {
            throw new \RuntimeException("API error: {$response->status()}");
        }

        $data = $response->json('data');
        $this->chartData = $this->formatComparisonData($data);
    }

    /**
     * Загрузить кастомные метрики
     */
    private function loadCustomMetricData(): void
    {
        $endpoint = $this->heatmapType === 'geo'
            ? '/api/analytics/heatmaps/custom/geo'
            : '/api/analytics/heatmaps/custom/click';

        $params = [
            'vertical' => $this->vertical,
            'metric' => $this->customMetric,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'aggregation' => $this->aggregation,
        ];

        if ($this->heatmapType === 'click') {
            $params['page_url'] = $this->pageUrl;
        }

        $response = $this->http->withHeader('X-Correlation-ID', $this->correlationId)
            ->timeout(30)
            ->get($this->config->get('app.url').$endpoint, $params);

        if ($response->failed()) {
            throw new \RuntimeException("API error: {$response->status()}");
        }

        $data = $response->json('data');
        $this->chartData = $this->formatCustomMetricData($data);
    }

    /**
     * Форматировать времеseries данные для Chart.js
     */
    private function formatTimeSeriesData(array $data): array
    {
        $labels = [];
        $values = [];

        foreach ($data['data'] ?? [] as $row) {
            $labels[] = $row['period'] ?? $row['date'] ?? '';

            if ($this->heatmapType === 'geo') {
                $values[] = $row[$this->metric] ?? 0;
            } else {
                $values[] = $row['click_count'] ?? 0;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $this->metric,
                    'data' => $values,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'metadata' => $data['metadata'] ?? [],
        ];
    }

    /**
     * Форматировать comparison данные
     */
    private function formatComparisonData(array $data): array
    {
        // Period 1 данные
        $period1Labels = [];
        $period1Values = [];

        // Period 2 данные
        $period2Labels = [];
        $period2Values = [];

        // Если есть данные по горячим точкам
        if (! empty($data['data'])) {
            foreach ($data['data'] as $index => $row) {
                $hash = $row['geo_hash'] ?? "{$index}";

                $period1Labels[] = $hash;
                $period1Values[] = $row['period1_value'] ?? 0;

                $period2Labels[] = $hash;
                $period2Values[] = $row['period2_value'] ?? 0;
            }
        }

        return [
            'labels' => array_merge($period1Labels, $period2Labels),
            'datasets' => [
                [
                    'label' => 'Период 1',
                    'data' => $period1Values,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                ],
                [
                    'label' => 'Период 2',
                    'data' => $period2Values,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                ],
            ],
            'metadata' => [
                'delta' => $data['delta'] ?? [],
                'period1' => $data['period1'] ?? [],
                'period2' => $data['period2'] ?? [],
            ],
        ];
    }

    /**
     * Форматировать кастомные метрики
     */
    private function formatCustomMetricData(array $data): array
    {
        $metricData = $data['data'] ?? [];

        // Простой график - значение метрики
        return [
            'labels' => ['Значение'],
            'datasets' => [
                [
                    'label' => $this->customMetric,
                    'data' => [
                        $metricData[$this->customMetric] ?? $metricData['engagement_score'] ?? 0,
                    ],
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.2)',
                ],
            ],
            'metadata' => $metricData['metadata'] ?? [],
        ];
    }

    /**
     * Построить конфиг для Chart.js
     */
    private function buildChartConfig(): void
    {
        $this->chartConfig = [
            'type' => $this->chartType,
            'data' => $this->chartData,
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => true,
                'plugins' => [
                    'legend' => [
                        'display' => $this->showLegend,
                        'position' => 'top',
                    ],
                    'tooltip' => [
                        'enabled' => $this->showTooltip,
                        'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                        'titleColor' => '#fff',
                        'bodyColor' => '#fff',
                        'borderColor' => 'rgba(255, 255, 255, 0.2)',
                        'borderWidth' => 1,
                        'padding' => 12,
                        'displayColors' => true,
                        'callbacks' => [
                            'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y; }',
                        ],
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => [
                            'display' => $this->showGrid,
                            'color' => 'rgba(0, 0, 0, 0.05)',
                        ],
                    ],
                    'x' => [
                        'grid' => [
                            'display' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
}
