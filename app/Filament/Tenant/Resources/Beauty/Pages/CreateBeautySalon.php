<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateBeautySalon extends CreateRecord
{
    protected static string $resource = BeautySalonResource::class;

    protected function beforeCreate(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        // 1. Audit Log: Start Creation Process
        Log::channel('audit')->info('Filament Resource: CreateBeautySalon Starting', [
            'tenant_id' => tenant('id'),
            'correlation_id' => $correlationId,
            'data' => $this->data
        ]);

        // 2. Fraud Check (КАНОН 2026)
        try {
            FraudControlService::check(
                userId: auth()->id(),
                operationType: 'create_beauty_salon',
                correlationId: $correlationId,
                amount: 0,
                metadata: $this->data
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Fraud Check Failed: CreateBeautySalon Blocked', [
                'tenant_id' => tenant('id'),
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            Notification::make()->title('Ошибка безопасности: Фрод-контроль')->danger()->send();
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

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Filament Resource: CreateBeautySalon Completed', [
            'id' => $this->record->id,
            'tenant_id' => tenant('id'),
            'correlation_id' => $this->record->correlation_id
        ]);
    }
}
