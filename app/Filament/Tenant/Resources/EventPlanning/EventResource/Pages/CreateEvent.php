<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning\EventResource\Pages;

use Filament\Notifications\Notification;

use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateEvent extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = EventResource::class;

        /**
         * Порядок создания:
         * 1. Мутация данных (ID, Correlation, UUID)
         * 2. Transaction Check (Fraud/Tenant)
         * 3. Доп. логика через Сервис (AI Generation если нужно)
         */
        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = (string) Str::uuid();

            \Illuminate\Support\Facades\Log::channel('audit')->info('Filament: Creating new event plan', [
                'tenant_id' => tenant()->id,
                'correlation_id' => $correlationId,
                'title' => $data['title'] ?? 'untitled',
            ]);

            $data['tenant_id'] = tenant()->id;
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = $correlationId;

            // По дефолту при создании ставим Драфт, если не указано
            $data['status'] = $data['status'] ?? 'draft';

            return $data;
        }

        /**
         * Редирект после создания — к списку.
         */
        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }

        /**
         * Нотификация об успехе.
         */
        protected function getCreatedNotification(): ?Notification
        {
            return Notification::make()
                ->success()
                ->title('Событие зарегистрировано')
                ->body('План праздника добавлен в систему планирования 2026.')
                ->icon('heroicon-o-check-badge');
        }
}
