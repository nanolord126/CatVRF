<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use Livewire\Component;

/**
 * Компонент для выбора двух периодов для сравнения
 */
final class ComparisonModePickerComponent extends Component
{
    public string $period1From = '';
    public string $period1To = '';
    public string $period2From = '';
    public string $period2To = '';
    public bool $isComparison = false;
    public array $presets = [];

    public function mount(): void
    {
        // Дефолтные даты - последние 30 дней
        $this->period1From = now()->subDays(30)->format('Y-m-d');
        $this->period1To = now()->subDays(15)->format('Y-m-d');
        $this->period2From = now()->subDays(15)->format('Y-m-d');
        $this->period2To = now()->format('Y-m-d');

        // Предустановки
        $this->presets = [
            'last-week-vs-previous' => [
                'label' => 'На этой неделе vs предыдущей',
                'period1_from' => now()->subWeek()->startOfWeek()->format('Y-m-d'),
                'period1_to' => now()->subWeek()->endOfWeek()->format('Y-m-d'),
                'period2_from' => now()->startOfWeek()->format('Y-m-d'),
                'period2_to' => now()->format('Y-m-d'),
            ],
            'this-month-vs-last' => [
                'label' => 'В этом месяце vs прошлого',
                'period1_from' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'period1_to' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
                'period2_from' => now()->startOfMonth()->format('Y-m-d'),
                'period2_to' => now()->format('Y-m-d'),
            ],
            'yoy' => [
                'label' => 'Год на год',
                'period1_from' => now()->subYear()->subMonth()->startOfMonth()->format('Y-m-d'),
                'period1_to' => now()->subYear()->endOfMonth()->format('Y-m-d'),
                'period2_from' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'period2_to' => now()->format('Y-m-d'),
            ],
        ];
    }

    public function toggleComparison(): void
    {
        $this->isComparison = !$this->isComparison;
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
        return view('livewire.analytics.comparison-mode-picker-component');
    }
}
