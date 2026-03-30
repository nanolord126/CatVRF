<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function beforeCreate(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        Log::channel('audit')->info('Filament Resource: CreateAppointment Starting', [
            'tenant_id' => tenant('id'),
            'correlation_id' => $correlationId,
            'data' => $this->data
        ]);

        try {
            FraudControlService::check(
                userId: auth()->id(),
                operationType: 'create_beauty_appointment',
                correlationId: $correlationId,
                amount: (int) ($this->data['price'] ?? 0),
                metadata: $this->data
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Security Block: CreateAppointment Fraud Check Failed', [
                'tenant_id' => tenant('id'),
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            Notification::make()->title('Запись заблокирована: Подозрительная активность')->danger()->send();
            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant('id');
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();
        $data['uuid'] = (string) Str::uuid();

        return $data;
    }
}
