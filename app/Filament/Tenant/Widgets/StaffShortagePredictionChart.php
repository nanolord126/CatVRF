<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\Common\AI\StaffPredictiveEngine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffShortagePredictionChart extends ChartWidget
{
    protected static ?string $heading = 'AI Staff Capacity vs Demand (Education/Sports)';
    protected static ?string $pollingInterval = '10s';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $today = Carbon::now();
        $engine = new StaffPredictiveEngine();
        
        // Execute dynamic simulation for the next 7 days for Education/Sports
        $dataForecasted = [];
        $dataAvailable = [];
        $labels = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->addDays($i);
            $labels[] = $date->format('M d (D)');
            
            // Forecast logic for simulation (Simulating dynamic demand flux)
            $eduStudy = $engine->forecastStaffing('Education', $date);
            $sportsStudy = $engine->forecastStaffing('Sports', $date);
            
            // Summed metrics for vertical health
            $dataForecasted[] = ($eduStudy['forecasted_staff'] + $sportsStudy['forecasted_staff']);
            $dataAvailable[] = ($eduStudy['available_staff'] + $sportsStudy['available_staff']);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Forecasted Staff Needed (AI)',
                    'data' => $dataForecasted,
                    'borderColor' => '#ef4444', // Red for demand
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Current Available Inventory',
                    'data' => $dataAvailable,
                    'borderColor' => '#22c55e', // Green for supply
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
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

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Staff Count (FTE)',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
