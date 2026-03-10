<?php

namespace App\Filament\Tenant\Resources\HRJobVacancyResource\Pages;

use App\Filament\Tenant\Resources\HRJobVacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHRJobVacancy extends EditRecord
{
    protected static string $resource = HRJobVacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('recalculateMatches')
                ->label('AI Match Recalculation')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->action(function () {
                    \App\Jobs\AI\ProcessHRAndB2BEmbeddingsJob::dispatch(
                        'vacancy', 
                        $this->record->id, 
                        $this->record->correlation_id
                    );
                    
                    \Filament\Notifications\Notification::make()
                        ->title('AI Matching Queued')
                        ->body('Vector similarity process started in the background.')
                        ->info()
                        ->send();
                }),
        ];
    }
}
