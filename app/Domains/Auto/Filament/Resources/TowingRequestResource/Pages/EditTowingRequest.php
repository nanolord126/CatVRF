<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TowingRequestResource\Pages;

use App\Domains\Auto\Filament\Resources\TowingRequestResource;
use App\Events\TowingCompleted;
use Filament\Pages\Actions;
use Filament\Notifications\Notification;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditTowingRequest extends EditRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    protected static string $resource = TowingRequestResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        $this->logger->info('TowingRequest deleted', [
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

                            $this->logger->info('TowingCompleted', [
                                'correlation_id' => $this->record->correlation_id,
                                'request_id' => $this->record->id,
                            ]);

                            event(new TowingCompleted(
                                $this->record,
                                $this->record->correlation_id
                            ));
                        });

                        Notification::make()
                            ->success()
                            ->title('Эвакуация завершена')
                            ->send();
                    }),
            ];
        }

        protected function afterSave(): void
        {
            $this->logger->info('TowingRequest updated', [
                'correlation_id' => $this->record->correlation_id,
                'request_id' => $this->record->id,
                'status' => $this->record->status,
            ]);
        }
}
