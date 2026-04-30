<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

use Livewire\Component;

final class ComparisonModePickerComponent extends Component
{
    private readonly string $period1From = '';

    private readonly string $period1To = '';

    private readonly string $period2From = '';

    private readonly string $period2To = '';

    private readonly bool $isComparison = false;

    private readonly array $presets = [];

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(): void
    {
        // Дефолтные даты - последние 30 дней
        $this->period1From = CarbonImmutable::now()->subDays(30)->format('Y-m-d');
        $this->period1To = CarbonImmutable::now()->subDays(15)->format('Y-m-d');
        $this->period2From = CarbonImmutable::now()->subDays(15)->format('Y-m-d');
        $this->period2To = CarbonImmutable::now()->format('Y-m-d');

        // Предустановки
        $this->presets = [
            'last-week-vs-previous' => [
                'label' => 'На этой неделе vs предыдущей',
                'period1_from' => CarbonImmutable::now()->subWeek()->startOfWeek()->format('Y-m-d'),
                'period1_to' => CarbonImmutable::now()->subWeek()->endOfWeek()->format('Y-m-d'),
                'period2_from' => CarbonImmutable::now()->startOfWeek()->format('Y-m-d'),
                'period2_to' => CarbonImmutable::now()->format('Y-m-d'),
            ],
            'this-month-vs-last' => [
                'label' => 'В этом месяце vs прошлого',
                'period1_from' => CarbonImmutable::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'period1_to' => CarbonImmutable::now()->subMonth()->endOfMonth()->format('Y-m-d'),
                'period2_from' => CarbonImmutable::now()->startOfMonth()->format('Y-m-d'),
                'period2_to' => CarbonImmutable::now()->format('Y-m-d'),
            ],
            'yoy' => [
                'label' => 'Год на год',
                'period1_from' => CarbonImmutable::now()->subYear()->subMonth()->startOfMonth()->format('Y-m-d'),
                'period1_to' => CarbonImmutable::now()->subYear()->endOfMonth()->format('Y-m-d'),
                'period2_from' => CarbonImmutable::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'period2_to' => CarbonImmutable::now()->format('Y-m-d'),
            ],
        ];
    }

    public function toggleComparison(): void
    {
        $this->isComparison = ! $this->isComparison;
        $this->dispatch('comparison-toggled', enabled: $this->isComparison);
    }

    public function applyPreset(string $preset): void
    {
        if (isset($this->presets[$preset])) {
            $p = $this->presets[$preset];
            $this->period1From = $p['period1_from'];
            $this->period1To = $p['period1_to'];
            $this->period2From = $p['period2_from'];
            $this->period2To = $p['period2_to'];
        }

        $this->dispatch('comparison-updated', [
            'period1_from' => $this->period1From,
            'period1_to' => $this->period1To,
            'period2_from' => $this->period2From,
            'period2_to' => $this->period2To,
        ]);
    }

    public function updateDates(): void
    {
        $this->dispatch('comparison-updated', [
            'period1_from' => $this->period1From,
            'period1_to' => $this->period1To,
            'period2_from' => $this->period2From,
            'period2_to' => $this->period2To,
        ]);
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.analytics.comparison-mode-picker-component');
    }
}
