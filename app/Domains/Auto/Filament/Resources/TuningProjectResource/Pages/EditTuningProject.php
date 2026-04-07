<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TuningProjectResource\Pages;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditTuningProject extends EditRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    protected static string $resource = TuningProjectResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        $this->logger->info('TuningProject deleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'project_id' => $this->record->id,
                        ]);
                    }),
                Actions\Action::make('complete')
                    ->label('Завершить проект')
                    ->visible(fn () => $this->record->status === 'in_progress')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('final_price')
                            ->label('Итоговая стоимость (копейки)')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $this->db->transaction(function () use ($data) {
                            $this->record->update([
                                'status' => 'completed',
                                'completion_date' => Carbon::now(),
                                'final_price' => $data['final_price'],
                            ]);

                            $this->logger->info('TuningProjectCompleted', [
                                'correlation_id' => $this->record->correlation_id,
                                'project_id' => $this->record->id,
                            ]);

                            event(new TuningProjectCompleted(
                                $this->record,
                                $this->record->correlation_id
                            ));
                        });

                        $this->notification->make()
                            ->success()
                            ->title('Тюнинг завершён')
                            ->send();
                    }),
            ];
        }

        protected function afterSave(): void
        {
            $this->logger->info('TuningProject updated', [
                'correlation_id' => $this->record->correlation_id,
                'project_id' => $this->record->id,
                'status' => $this->record->status,
            ]);
        }
}
