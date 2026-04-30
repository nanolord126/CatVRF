<?php

declare(strict_types=1);

namespace App\Filament\Resources\DriftMonitoringResource\Pages;

use App\Filament\Resources\DriftMonitoringResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDriftMonitoring extends ListRecords
{
    protected static string $resource = DriftMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-chart-pie')
                ->url(fn () => DriftMonitoringResource::getUrl('dashboard')),
            Actions\Action::make('trigger_all_analysis')
                ->label('Trigger All Analysis')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    \App\Jobs\ML\DailyDriftAnalysisJob::dispatch();

                    \Filament\Notifications\Notification::make()
                        ->title('All Analysis Triggered')
                        ->body('Drift analysis started for all models and verticals')
                        ->success()
                        ->send();
                }),
        ];
    }
}
