<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AutoServiceOrderResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\DatabaseManager;

final class EditAutoServiceOrder extends EditRecord
{
    protected static string $resource = AutoServiceOrderResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('complete')
                ->label('Завершить работы')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['pending', 'in_progress'], true))
                ->form([
                    Forms\Components\TextInput::make('final_price')
                        ->label('Итоговая стоимость (копейки)')
                        ->numeric()
                        ->required(),

                    Forms\Components\Textarea::make('work_description')
                        ->label('Описание выполненных работ')
                        ->rows(5)
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Примечания')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->db->transaction(function () use ($data) {
                        $this->record->status = 'completed';
                        $this->record->total_price = (int) $data['final_price'];
                        $this->record->work_description = $data['work_description'];
                        $this->record->notes = $data['notes'] ?? null;
                        $this->record->completed_at = CarbonImmutable::now();
                        $this->record->save();

                        $this->eventDispatcher->dispatch(new AutoServiceOrderCompleted(
                            $this->record,
                            $this->record->correlation_id
                        ));

                        $this->logger->$this->logger->info('Auto service order completed', [
                            'correlation_id' => $this->record->correlation_id,
                            'order_id' => $this->record->id,
                            'final_price' => $data['final_price'],
                            'completed_at' => $this->record->completed_at,
                            'user_id' => $this->guard->id(),
                        ]);

                        $this->notification->make()
                            ->title('Заказ-наряд завершён')
                            ->body('Услуга выполнена, стоимость: '.($data['final_price'] / 100).' ₽')
                            ->success()
                            ->send();
                    });
                }),

            Actions\DeleteAction::make()
                ->after(function () {
                    $this->logger->$this->logger->info('Auto service order deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'order_id' => $this->record->id,
                        'user_id' => $this->guard->id(),
                    ]);
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->logger->$this->logger->info('Auto service order updated', [
            'correlation_id' => $this->record->correlation_id,
            'order_id' => $this->record->id,
            'status' => $this->record->status,
            'user_id' => $this->guard->id(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
