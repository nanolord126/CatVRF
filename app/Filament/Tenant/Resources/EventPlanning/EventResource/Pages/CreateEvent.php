<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning\EventResource\Pages;

use Illuminate\Notifications\ChannelManager;

use Psr\Log\LoggerInterface;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;

final class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Порядок создания:
     * 1. Мутация данных (ID, Correlation, UUID)
     * 2. Transaction Check (Fraud/Tenant)
     * 3. Доп. логика через Сервис (AI Generation если нужно)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = (string) Str::uuid();

        $this->log->channel('audit')->$this->logger->info('Filament: Creating new event plan', [
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
        return $this->notificationManager->make()
            ->success()
            ->title('Событие зарегистрировано')
            ->body('План праздника добавлен в систему планирования 2026.')
            ->icon('heroicon-o-check-badge');
    }
}
