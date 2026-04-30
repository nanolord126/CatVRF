<?php

declare(strict_types=1);

namespace App\Filament\Resources\DriftMonitoringResource\Pages;

use App\Filament\Resources\DriftMonitoringResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDriftMonitoring extends ViewRecord
{
    protected static string $resource = DriftMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('trigger_analysis')
                ->label('Trigger Analysis')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->getRecord();
                    \App\Jobs\ML\DailyDriftAnalysisJob::dispatch(
                        $record->model_type,
                        $record->vertical_code
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Analysis Triggered')
                        ->success()
                        ->send();
                }),
        ];
    }
}
