<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarDetailingResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\DatabaseManager;

final class EditCarDetailing extends EditRecord
{
    protected static string $resource = CarDetailingResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    $this->logger->$this->logger->info('CarDetailing deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'detailing_id' => $this->record->id,
                    ]);
                }),
            Actions\Action::make('complete')
                ->label('Завершить')
                ->visible(fn () => $this->record->status === 'in_progress')
                ->requiresConfirmation()
                ->action(function () {
                    $this->db->transaction(function () {
                        $this->record->update(['status' => 'completed']);

                        $this->logger->$this->logger->info('DetailingCompleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'detailing_id' => $this->record->id,
                        ]);

                        $this->eventDispatcher->dispatch(new DetailingCompleted(
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
        $this->logger->$this->logger->info('CarDetailing updated', [
            'correlation_id' => $this->record->correlation_id,
            'detailing_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
