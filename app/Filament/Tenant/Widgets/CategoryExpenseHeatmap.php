<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Inventory\Models\Product;
use Illuminate\Support\Facades\DB;

class CategoryExpenseHeatmap extends ChartWidget
{
    protected static ?string $heading = 'Inventory Cat Exp Heatmap';
    protected static ?string $navigationGroup = 'Inventory';

    protected function getData(): array
    {
        $data = Product::query()
            ->select('category', DB::raw('SUM(stock * price) as total_value'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Value of Inventory per Category ($)',
                    'data' => $data->pluck('total_value')->toArray(),
                    'backgroundColor' => ['#f87171', '#fbbf24', '#34d399', '#60a5fa', '#a78bfa'],
                ],
            ],
            'labels' => $data->pluck('category')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
