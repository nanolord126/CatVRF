<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleRentalResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\DatabaseManager;

final class EditVehicleRental extends EditRecord
{
    protected static string $resource = VehicleRentalResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    $this->logger->$this->logger->info('VehicleRental deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'rental_id' => $this->record->id,
                    ]);
                }),
            Actions\Action::make('complete')
                ->label('Завершить аренду')
                ->visible(fn () => $this->record->status === 'active')
                ->requiresConfirmation()
                ->form([
                    TextInput::make('final_mileage')
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

                        $this->logger->$this->logger->info('VehicleRentalCompleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'rental_id' => $this->record->id,
                            'final_mileage' => $data['final_mileage'],
                        ]);

                        $this->eventDispatcher->dispatch(new VehicleRentalCompleted(
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
        $this->logger->$this->logger->info('VehicleRental updated', [
            'correlation_id' => $this->record->correlation_id,
            'rental_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
