<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TowingRequestResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditTowingRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = TowingRequestResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('TowingRequest deleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'request_id' => $this->record->id,
                        ]);
                    }),
                Actions\Action::make('complete')
                    ->label('Завершить')
                    ->visible(fn () => $this->record->status === 'in_progress')
                    ->requiresConfirmation()
                    ->action(function () {
                        DB::transaction(function () {
                            $this->record->update(['status' => 'completed']);

                            Log::channel('audit')->info('TowingCompleted', [
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
            Log::channel('audit')->info('TowingRequest updated', [
                'correlation_id' => $this->record->correlation_id,
                'request_id' => $this->record->id,
                'status' => $this->record->status,
            ]);
        }
}
