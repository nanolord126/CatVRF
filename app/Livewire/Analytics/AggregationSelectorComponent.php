<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

final class AggregationSelectorComponent extends Component
{
    protected $listeners = ['update-aggregation'];

    private readonly string $aggregation = 'daily';

    private readonly array $selectedMetrics = [];

    private readonly bool $showLabels = true;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function updateAggregation(string $aggregation): void
    {
        $this->aggregation = $aggregation;
        $this->dispatch('aggregation-changed', aggregation: $aggregation);
    }

    public function toggleMetric(string $metric): void
    {
        if (in_array($metric, $this->selectedMetrics, true)) {
            $this->selectedMetrics = array_values(
                array_filter($this->selectedMetrics, fn ($m) => $m !== $metric)
            );
        } else {
            $this->selectedMetrics[] = $metric;
        }

        $this->dispatch('metrics-changed', metrics: $this->selectedMetrics);
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.analytics.aggregation-selector-component', [
            'aggregations' => [
                'hourly' => [
                    'label' => 'Ежечасно',
                    'description' => 'Данные каждый час',
                    'icon' => '🕐',
                ],
                'daily' => [
                    'label' => 'Ежедневно',
                    'description' => 'Данные каждый день',
                    'icon' => '📅',
                ],
                'weekly' => [
                    'label' => 'Еженедельно',
                    'description' => 'Данные каждую неделю',
                    'icon' => '📊',
                ],
            ],
            'availableMetrics' => [
                'event_count' => 'События',
                'unique_users' => 'Уникальные пользователи',
                'unique_sessions' => 'Уникальные сессии',
            ],
        ]);
    }
}
