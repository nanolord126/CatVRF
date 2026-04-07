<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleRentalResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditVehicleRental extends EditRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    protected static string $resource = VehicleRentalResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        $this->logger->info('VehicleRental deleted', [
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
                        $this->db->transaction(function () use ($data) {
                            $this->record->update([
                                'status' => 'completed',
                                'final_mileage' => $data['final_mileage'],
                            ]);

                            $this->logger->info('VehicleRentalCompleted', [
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
            $this->logger->info('VehicleRental updated', [
                'correlation_id' => $this->record->correlation_id,
                'rental_id' => $this->record->id,
                'status' => $this->record->status,
            ]);
        }
}
