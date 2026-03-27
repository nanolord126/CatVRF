<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditBeautySalon extends EditRecord
{
    protected static string $resource = BeautySalonResource::class;

    protected function beforeSave(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        // 1. Audit Log: Start Saving Process
        Log::channel('audit')->info('Filament Resource: EditBeautySalon Starting', [
            'tenant_id' => tenant('id'),
            'id' => $this->record->id,
            'correlation_id' => $correlationId,
            'data' => $this->data
        ]);

        // 2. Fraud Check (КАНОН 2026)
        try {
            FraudControlService::check(
                userId: auth()->id(),
                operationType: 'update_beauty_salon',
                correlationId: $correlationId,
                amount: 0,
                metadata: $this->data
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Fraud Check Failed: EditBeautySalon Blocked', [
                'tenant_id' => tenant('id'),
                'id' => $this->record->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            Notification::make()->title('Ошибка безопасности: Фрод-контроль')->danger()->send();
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Filament Resource: EditBeautySalon Completed', [
            'id' => $this->record->id,
            'tenant_id' => tenant('id'),
            'correlation_id' => $this->record->correlation_id ?? (string) Str::uuid()
        ]);
    }
}
