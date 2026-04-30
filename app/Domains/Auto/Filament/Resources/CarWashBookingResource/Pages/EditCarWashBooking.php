<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\DatabaseManager;

final class EditCarWashBooking extends EditRecord
{
    protected static string $resource = CarWashBookingResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('complete')
                ->label('Завершить мойку')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['pending', 'in_progress'], true))
                ->requiresConfirmation()
                ->action(function () {
                    $this->db->transaction(function () {
                        $this->record->status = 'completed';
                        $this->record->completed_at = CarbonImmutable::now();
                        $this->record->save();

                        $this->eventDispatcher->dispatch(new CarWashCompleted(
                            $this->record,
                            $this->record->correlation_id
                        ));

                        $this->logger->$this->logger->info('Car wash booking completed', [
                            'correlation_id' => $this->record->correlation_id,
                            'booking_id' => $this->record->id,
                            'completed_at' => $this->record->completed_at,
                            'user_id' => $this->guard->id(),
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
                    $this->logger->$this->logger->info('Car wash booking deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'booking_id' => $this->record->id,
                        'user_id' => $this->guard->id(),
                    ]);
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->logger->$this->logger->info('Car wash booking updated', [
            'correlation_id' => $this->record->correlation_id,
            'booking_id' => $this->record->id,
            'status' => $this->record->status,
            'user_id' => $this->guard->id(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
