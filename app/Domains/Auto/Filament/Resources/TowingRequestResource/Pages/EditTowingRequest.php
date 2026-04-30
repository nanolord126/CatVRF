<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TowingRequestResource\Pages;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Domains\Auto\Filament\Resources\TowingRequestResource;
use App\Events\TowingCompleted;
use Filament\Pages\Actions;
use Filament\Notifications\Notification;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\DatabaseManager;

final class EditTowingRequest extends EditRecord
{
    protected static string $resource = TowingRequestResource::class;

    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    $this->logger->$this->logger->info('TowingRequest deleted', [
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

                        $this->logger->$this->logger->info('TowingCompleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'request_id' => $this->record->id,
                        ]);

                        $this->eventDispatcher->dispatch(new TowingCompleted(
                            $this->record,
                            $this->record->correlation_id
                        ));
                    });

                    $this->notificationManager->make()
                        ->success()
                        ->title('Эвакуация завершена')
                        ->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->logger->$this->logger->info('TowingRequest updated', [
            'correlation_id' => $this->record->correlation_id,
            'request_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
