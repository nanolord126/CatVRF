<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarDetailingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditCarDetailing extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CarDetailingResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('CarDetailing deleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'detailing_id' => $this->record->id,
                        ]);
                    }),
                Actions\Action::make('complete')
                    ->label('Завершить')
                    ->visible(fn () => $this->record->status === 'in_progress')
                    ->requiresConfirmation()
                    ->action(function () {
                        DB::transaction(function () {
                            $this->record->update(['status' => 'completed']);

                            Log::channel('audit')->info('DetailingCompleted', [
                                'correlation_id' => $this->record->correlation_id,
                                'detailing_id' => $this->record->id,
                            ]);

                            event(new DetailingCompleted(
                                $this->record,
                                $this->record->correlation_id
                            ));
                        });

                        $this->notification->make()
                            ->success()
                            ->title('Детейлинг завершён')
                            ->send();
                    }),
            ];
        }

        protected function afterSave(): void
        {
            Log::channel('audit')->info('CarDetailing updated', [
                'correlation_id' => $this->record->correlation_id,
                'detailing_id' => $this->record->id,
                'status' => $this->record->status,
            ]);
        }
}
