<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function beforeSave(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        Log::channel('audit')->info('Filament Resource: EditAppointment Starting', [
            'tenant_id' => tenant('id'),
            'id' => $this->record->id,
            'correlation_id' => $correlationId,
            'data' => $this->data
        ]);

        try {
            FraudControlService::check(
                userId: auth()->id(),
                operationType: 'update_beauty_appointment',
                correlationId: $correlationId,
                amount: (int) ($this->data['price'] ?? 0),
                metadata: $this->data
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Security Block: EditAppointment Fraud Check Failed', [
                'tenant_id' => tenant('id'),
                'id' => $this->record->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            Notification::make()->title('Изменение заблокировано фрод-контролем')->danger()->send();
            $this->halt();
        }
    }
}
