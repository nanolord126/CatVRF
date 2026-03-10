<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;

class FraudAnomalyChartWidget extends ChartWidget
{
    protected static ?string $heading = 'AI Fraud Activity (Anomalies)';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'half';

    protected function getData(): array
    {
        $tenantId = Tenancy::tenant()->getTenantKey();

        // Статистика инцидентов фрода за последние 24 часа
        $data = DB::table('fraud_events')
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as total'))
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->all();

        // Заполнение пропусков по часам для красивого графика
        $labels = [];
        $values = [];
        for ($i = 0; $i < 24; $i++) {
            $labels[] = "{$i}:00";
            $values[] = $data[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Anomalies Detected',
                    'data' => $values,
                    'borderColor' => '#ef4444', // Красный риск
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
