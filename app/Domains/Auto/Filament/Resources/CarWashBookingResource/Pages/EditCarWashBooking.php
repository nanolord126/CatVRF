<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditCarWashBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CarWashBookingResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\Action::make('complete')
                    ->label('Завершить мойку')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn () => in_array($this->record->status, ['pending', 'in_progress']))
                    ->requiresConfirmation()
                    ->action(function () {
                        DB::transaction(function () {
                            $this->record->status = 'completed';
                            $this->record->completed_at = now();
                            $this->record->save();

                            event(new CarWashCompleted(
                                $this->record,
                                $this->record->correlation_id
                            ));

                            Log::channel('audit')->info('Car wash booking completed', [
                                'correlation_id' => $this->record->correlation_id,
                                'booking_id' => $this->record->id,
                                'completed_at' => $this->record->completed_at,
                                'user_id' => auth()->id(),
                            ]);

                            $this->notification->make()
                                ->title('Мойка завершена')
                                ->body("Тип мойки: {$this->record->wash_type}")
                                ->success()
                                ->send();
                        });
                    }),

                Actions\DeleteAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('Car wash booking deleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'booking_id' => $this->record->id,
                            'user_id' => auth()->id(),
                        ]);
                    }),
            ];
        }

        protected function afterSave(): void
        {
            Log::channel('audit')->info('Car wash booking updated', [
                'correlation_id' => $this->record->correlation_id,
                'booking_id' => $this->record->id,
                'status' => $this->record->status,
                'user_id' => auth()->id(),
            ]);
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
