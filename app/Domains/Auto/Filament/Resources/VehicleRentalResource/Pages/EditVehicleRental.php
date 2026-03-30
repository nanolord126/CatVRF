<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleRentalResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditVehicleRental extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VehicleRentalResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('VehicleRental deleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'rental_id' => $this->record->id,
                        ]);
                    }),
                Actions\Action::make('complete')
                    ->label('Завершить аренду')
                    ->visible(fn () => $this->record->status === 'active')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('final_mileage')
                            ->label('Финальный пробег (км)')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        DB::transaction(function () use ($data) {
                            $this->record->update([
                                'status' => 'completed',
                                'final_mileage' => $data['final_mileage'],
                            ]);

                            Log::channel('audit')->info('VehicleRentalCompleted', [
                                'correlation_id' => $this->record->correlation_id,
                                'rental_id' => $this->record->id,
                                'final_mileage' => $data['final_mileage'],
                            ]);

                            event(new VehicleRentalCompleted(
                                $this->record,
                                (int) $data['final_mileage'],
                                $this->record->correlation_id
                            ));
                        });

                        $this->notification->make()
                            ->success()
                            ->title('Аренда завершена')
                            ->send();
                    }),
            ];
        }

        protected function afterSave(): void
        {
            Log::channel('audit')->info('VehicleRental updated', [
                'correlation_id' => $this->record->correlation_id,
                'rental_id' => $this->record->id,
                'status' => $this->record->status,
            ]);
        }
}
