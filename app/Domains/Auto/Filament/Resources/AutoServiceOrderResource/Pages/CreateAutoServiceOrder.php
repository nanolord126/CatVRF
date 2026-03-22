<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AutoServiceOrderResource\Pages;

use App\Domains\Auto\Filament\Resources\AutoServiceOrderResource;
use App\Domains\Auto\Events\AutoServiceOrderCreated;
use App\Services\FraudControlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

/**
 * Создание заказа-наряда СТО с fraud check, транзакцией и audit-логом.
 * Production 2026.
 */
final class CreateAutoServiceOrder extends CreateRecord
{
    protected static string $resource = AutoServiceOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;
        $data['status'] = 'pending';

        // Fraud check перед созданием
        $fraudCheck = FraudControlService::check([
            'operation' => 'create_auto_service_order',
            'tenant_id' => filament()->getTenant()->id,
            'user_id' => auth()->id(),
            'data' => $data,
        ]);

        if (!$fraudCheck['allowed']) {
            Notification::make()
                ->title('Подозрение на мошенничество')
                ->body($fraudCheck['reason'] ?? 'Операция заблокирована')
                ->danger()
                ->send();

            $this->halt();
        }

        Log::channel('audit')->info('Creating auto service order', [
            'correlation_id' => $correlationId,
            'tenant_id' => filament()->getTenant()->id,
            'user_id' => auth()->id(),
            'data' => $data,
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            // Событие создания заказа-наряда
            event(new AutoServiceOrderCreated(
                $this->record,
                $this->record->correlation_id
            ));

            Log::channel('audit')->info('Auto service order created successfully', [
                'correlation_id' => $this->record->correlation_id,
                'order_id' => $this->record->id,
                'service_type' => $this->record->service_type,
                'appointment_datetime' => $this->record->appointment_datetime,
                'tenant_id' => filament()->getTenant()->id,
            ]);

            Notification::make()
                ->title('Заказ-наряд создан')
                ->body("Услуга: {$this->record->service_type}, дата: {$this->record->appointment_datetime->format('d.m.Y H:i')}")
                ->success()
                ->send();
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
