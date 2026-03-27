<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditMaster extends EditRecord
{
    protected static string $resource = MasterResource::class;

    protected function beforeSave(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        Log::channel('audit')->info('Filament Resource: EditMaster Starting', [
            'tenant_id' => tenant('id'),
            'id' => $this->record->id,
            'correlation_id' => $correlationId,
            'data' => $this->data
        ]);

        try {
            FraudControlService::check(
                userId: auth()->id(),
                operationType: 'update_beauty_master',
                correlationId: $correlationId,
                amount: 0,
                metadata: $this->data
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Security Block: EditMaster Fraud Score Too High', [
                'tenant_id' => tenant('id'),
                'id' => $this->record->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            Notification::make()->title('Отказ в сохранении: Фрод-контроль')->danger()->send();
            $this->halt();
        }
    }
}
