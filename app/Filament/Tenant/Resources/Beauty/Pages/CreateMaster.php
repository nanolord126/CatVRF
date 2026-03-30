<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateMaster extends CreateRecord
{
    protected static string $resource = MasterResource::class;

    protected function beforeCreate(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        Log::channel('audit')->info('Filament Resource: CreateMaster Starting', [
            'tenant_id' => tenant('id'),
            'correlation_id' => $correlationId,
            'data' => $this->data
        ]);

        try {
            FraudControlService::check(
                userId: auth()->id(),
                operationType: 'create_beauty_master',
                correlationId: $correlationId,
                amount: 0,
                metadata: $this->data
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Security Block: CreateMaster Fraud Score Too High', [
                'tenant_id' => tenant('id'),
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            Notification::make()->title('Отказ в регистрации: Фрод-контроль')->danger()->send();
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
