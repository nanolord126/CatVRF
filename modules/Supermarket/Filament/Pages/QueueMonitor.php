<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Pages;

use App\Services\QueueMetricsService;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class QueueMonitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Мониторинг очередей';
    protected static ?string $navigationGroup = 'Supermarket';
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.supermarket.pages.queue-monitor';

    public array $queueMetrics = [];

    public function mount(): void
    {
        $metricsService = app(QueueMetricsService::class);
        
        $this->queueMetrics = [
            'supermarket' => $metricsService->getHealthStatus('supermarket'),
            'supermarket-high' => $metricsService->getHealthStatus('supermarket-high'),
            'supermarket-low' => $metricsService->getHealthStatus('supermarket-low'),
            'crm-sync' => $metricsService->getHealthStatus('crm-sync'),
        ];
    }

    public function refresh(): void
    {
        $this->mount();
    }

    public function getViewData(): array
    {
        return [
            'queueMetrics' => $this->queueMetrics,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        $metrics = $this->queueMetrics;

        return [
            StatsOverviewWidget::make([
                StatsOverviewWidget\Stat::make('Supermarket', $metrics['supermarket']['metrics']['total_jobs'])
                    ->description($metrics['supermarket']['health'])
                    ->descriptionIcon($this->getHealthIcon($metrics['supermarket']['health']))
                    ->color($this->getHealthColor($metrics['supermarket']['health'])),

                StatsOverviewWidget\Stat::make('High Priority', $metrics['supermarket-high']['metrics']['total_jobs'])
                    ->description($metrics['supermarket-high']['health'])
                    ->descriptionIcon($this->getHealthIcon($metrics['supermarket-high']['health']))
                    ->color($this->getHealthColor($metrics['supermarket-high']['health'])),

                StatsOverviewWidget\Stat::make('Low Priority', $metrics['supermarket-low']['metrics']['total_jobs'])
                    ->description($metrics['supermarket-low']['health'])
                    ->descriptionIcon($this->getHealthIcon($metrics['supermarket-low']['health']))
                    ->color($this->getHealthColor($metrics['supermarket-low']['health'])),

                StatsOverviewWidget\Stat::make('CRM Sync', $metrics['crm-sync']['metrics']['total_jobs'])
                    ->description($metrics['crm-sync']['health'])
                    ->descriptionIcon($this->getHealthIcon($metrics['crm-sync']['health']))
                    ->color($this->getHealthColor($metrics['crm-sync']['health'])),
            ]),
        ];
    }

    private function getHealthIcon(string $health): string
    {
        return match ($health) {
            'healthy' => 'heroicon-m-check-circle',
            'warning' => 'heroicon-m-exclamation-triangle',
            'critical' => 'heroicon-m-x-circle',
            default => 'heroicon-m-question-mark-circle',
        };
    }

    private function getHealthColor(string $health): string
    {
        return match ($health) {
            'healthy' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }
}
