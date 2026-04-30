<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning\EventResource\Pages;

use Illuminate\Notifications\ChannelManager;

use Psr\Log\LoggerInterface;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;

final class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Header Actions — Кнопки действий над событием.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить План')
                ->icon('heroicon-o-trash'),

            Actions\Action::make('Отменить')
                ->label('Отменить Событие')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);

                    $this->logger->warning('Filament: Event cancelled manual', [
                        'event_uuid' => $this->record->uuid,
                        'user_id' => auth()->id(),
                    ]);

                    $this->notificationManager->make()
                        ->danger()
                        ->title('Событие отменено')
                        ->body('Статус события обновлен до: cancelled.')
                        ->send();
                }),

            Actions\Action::make('Подтвердить')
                ->label('Подтвердить План')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->record->update(['status' => 'confirmed']);

                    $this->log->channel('audit')->$this->logger->info('Filament: Event confirmed manual', [
                        'event_uuid' => $this->record->uuid,
                        'user_id' => auth()->id(),
                    ]);

                    $this->notificationManager->make()
                        ->success()
                        ->title('Событие подтверждено')
                        ->body('Праздник теперь в активной фазе планирования.')
                        ->send();
                }),
        ];
    }

    /**
     * Мутация данных перед сохранением.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->log->channel('audit')->$this->logger->info('Filament: Plan modified', [
            'event_uuid' => $this->record->uuid,
            'tenant_id' => tenant()->id,
            'modified_by' => auth()->id(),
        ]);

        return $data;
    }

    /**
     * Редирект после редактирования — к списку.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Нотификация об успехе.
     */
    protected function getSavedNotification(): ?Notification
    {
        return $this->notificationManager->make()
            ->success()
            ->title('Изменения сохранены')
            ->body('План обновлен в реестре для всех вендоров.')
            ->icon('heroicon-o-pencil-square');
    }
}
