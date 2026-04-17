<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning\EventResource\Pages;

use Filament\Notifications\Notification;


use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditEvent extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = EventResource::class;

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
                            'user_id' => auth()->id()
                        ]);

                        Notification::make()
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

                        \Illuminate\Support\Facades\Log::channel('audit')->info('Filament: Event confirmed manual', [
                            'event_uuid' => $this->record->uuid,
                            'user_id' => auth()->id()
                        ]);

                        Notification::make()
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
            \Illuminate\Support\Facades\Log::channel('audit')->info('Filament: Plan modified', [
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
            return Notification::make()
                ->success()
                ->title('Изменения сохранены')
                ->body('План обновлен в реестре для всех вендоров.')
                ->icon('heroicon-o-pencil-square');
        }
}
