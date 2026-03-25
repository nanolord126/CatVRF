<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TowingRequestResource\Pages;

use App\Domains\Auto\Filament\Resources\TowingRequestResource;
use App\Domains\Auto\Events\TowingCompleted;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class EditTowingRequest extends EditRecord
{
    protected static string $resource = TowingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    $this->log->channel('audit')->info('TowingRequest deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'request_id' => $this->record->id,
                    ]);
                }),
            Actions\Action::make('complete')
                ->label('Завершить')
                ->visible(fn () => $this->record->status === 'in_progress')
                ->requiresConfirmation()
                ->action(function () {
                    $this->db->transaction(function () {
                        $this->record->update(['status' => 'completed']);
                        
                        $this->log->channel('audit')->info('TowingCompleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'request_id' => $this->record->id,
                        ]);

                        event(new TowingCompleted(
                            $this->record,
                            $this->record->correlation_id
                        ));
                    });

                    $this->notification->make()
                        ->success()
                        ->title('Эвакуация завершена')
                        ->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->log->channel('audit')->info('TowingRequest updated', [
            'correlation_id' => $this->record->correlation_id,
            'request_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
