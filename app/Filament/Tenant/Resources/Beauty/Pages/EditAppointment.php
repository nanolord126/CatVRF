<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Редактирование записи. Filament Page.
 *
 * Сервисы резолвятся через app().
 * Нет constructor injection, нет Facades.
 * FraudControlService::check() + correlation_id + audit-лог.
 *
 * @package App\Filament\Tenant\Resources\Beauty\Pages
 */
final class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function beforeSave(): void
    {
        $logger = app(LoggerInterface::class);
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();
        $tenantId = filament()->getTenant()?->id;

        $logger->info('Filament Resource: EditAppointment Starting', [
            'tenant_id'      => $tenantId,
            'id'             => $this->record->id,
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
            $logger->error('Security Block: EditAppointment Fraud Check Failed', [
                'tenant_id'      => $tenantId,
                'id'             => $this->record->id,
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Изменение заблокировано фрод-контролем')
                ->danger()
                ->send();
            $this->halt();
        }
    }
}
