<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TuningProjectResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\DatabaseManager;

final class EditTuningProject extends EditRecord
{
    protected static string $resource = TuningProjectResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    $this->logger->$this->logger->info('TuningProject deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'project_id' => $this->record->id,
                    ]);
                }),
            Actions\Action::make('complete')
                ->label('Завершить проект')
                ->visible(fn () => $this->record->status === 'in_progress')
                ->requiresConfirmation()
                ->form([
                    TextInput::make('final_price')
                        ->label('Итоговая стоимость (копейки)')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->db->transaction(function () use ($data) {
                        $this->record->update([
                            'status' => 'completed',
                            'completion_date' => CarbonImmutable::now(),
                            'final_price' => $data['final_price'],
                        ]);

                        $this->logger->$this->logger->info('TuningProjectCompleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'project_id' => $this->record->id,
                        ]);

                        $this->eventDispatcher->dispatch(new TuningProjectCompleted(
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
        $this->logger->$this->logger->info('TuningProject updated', [
            'correlation_id' => $this->record->correlation_id,
            'project_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
