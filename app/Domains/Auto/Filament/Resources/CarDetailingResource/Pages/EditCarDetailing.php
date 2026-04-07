<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarDetailingResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditCarDetailing extends EditRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    protected static string $resource = CarDetailingResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        $this->logger->info('CarDetailing deleted', [
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

                            $this->logger->info('DetailingCompleted', [
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
            $this->logger->info('CarDetailing updated', [
                'correlation_id' => $this->record->correlation_id,
                'detailing_id' => $this->record->id,
                'status' => $this->record->status,
            ]);
        }
}
