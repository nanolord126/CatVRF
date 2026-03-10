<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Common\HealthRecommendation;
use Illuminate\Support\Facades\Auth;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class HealthComplianceChart extends ChartWidget
{
    protected static ?string $heading = 'Аналитика Здоровья: Дисциплина выполнения (Compliance)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Имитация данных для наглядности (в 2026 году данные берутся из history_log)
        // Считаем % выполненных задач за последние 7 дней
        $data = [
            'labels' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
            'datasets' => [
                [
                    'label' => 'Выполнение рекомендаций (%)',
                    'data' => [85, 90, 75, 95, 100, 80, 92], // Реалистичные данные дисциплины
                    'borderColor' => '#10b981',
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => 'Уход за питомцами 🐾 (%)',
                    'data' => [60, 70, 80, 70, 90, 85, 95],
                    'borderColor' => '#8b5cf6',
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                ],
            ],
        ];

        return $data;
    }

    protected function getType(): string
    {
        return 'line';
    }
}
