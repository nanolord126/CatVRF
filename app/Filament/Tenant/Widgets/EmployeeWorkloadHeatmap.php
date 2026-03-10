<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EmployeeWorkloadHeatmap extends ChartWidget
{
    protected static ?string $heading = 'Employee Workload (Heatmap Strategy)';
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // В Filament стандартно нет heatmap, имитируем через Line/Bar chart по дням недели или часам
        // Для примера: суммарные часы по дням последней недели
        $data = Attendance::query()
            ->where('date', '>=', now()->subDays(7))
            ->select('date', DB::raw('SUM(total_hours) as aggregate'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Hours Worked',
                    'data' => $data->map(fn ($row) => $row->aggregate),
                    'backgroundColor' => '#fbbf24',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $data->map(fn ($row) => $row->date->format('M d')),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
