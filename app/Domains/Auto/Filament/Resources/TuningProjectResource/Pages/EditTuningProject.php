<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TuningProjectResource\Pages;

use App\Domains\Auto\Filament\Resources\TuningProjectResource;
use App\Domains\Auto\Events\TuningProjectCompleted;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class EditTuningProject extends EditRecord
{
    protected static string $resource = TuningProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    Log::channel('audit')->info('TuningProject deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'project_id' => $this->record->id,
                    ]);
                }),
            Actions\Action::make('complete')
                ->label('Завершить проект')
                ->visible(fn () => $this->record->status === 'in_progress')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\TextInput::make('final_price')
                        ->label('Итоговая стоимость (копейки)')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $this->record->update([
                            'status' => 'completed',
                            'completion_date' => now(),
                            'final_price' => $data['final_price'],
                        ]);
                        
                        Log::channel('audit')->info('TuningProjectCompleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'project_id' => $this->record->id,
                        ]);

                        event(new TuningProjectCompleted(
                            $this->record,
                            $this->record->correlation_id
                        ));
                    });

                    $this->notification->make()
                        ->success()
                        ->title('Тюнинг завершён')
                        ->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('TuningProject updated', [
            'correlation_id' => $this->record->correlation_id,
            'project_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
