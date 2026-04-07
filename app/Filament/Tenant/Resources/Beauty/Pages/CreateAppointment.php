<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Создание записи к мастеру. Filament Page.
 *
 * Сервисы резолвятся через app().
 * Нет constructor injection, нет Facades.
 * FraudControlService::check() + correlation_id + audit-лог.
 *
 * @package App\Filament\Tenant\Resources\Beauty\Pages
 */
final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function beforeCreate(): void
    {
        $logger = app(LoggerInterface::class);
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();
        $tenantId = filament()->getTenant()?->id;

        $logger->info('Filament Resource: CreateAppointment Starting', [
            'tenant_id'      => $tenantId,
            'correlation_id' => $correlationId,
            'data'           => $this->data,
        ]);

        try {
            app(FraudControlService::class)->check(
                userId: filament()->auth()->id() ?? 0,
                operationType: 'mutation',
                amount: 0,
                correlationId: $correlationId,
            );
        } catch (\Throwable $e) {
            $logger->error('Security Block: CreateAppointment Fraud Check Failed', [
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Запись заблокирована: Подозрительная активность')
                ->danger()
                ->send();
            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = filament()->getTenant()?->id;
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();
        $data['uuid']           = (string) Str::uuid();

        return $data;
    }
}
