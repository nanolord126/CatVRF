<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AutoPartResource\Pages;

use App\Domains\Auto\Filament\Resources\AutoPartResource;
use App\Domains\Auto\Events\AutoPartCreated;
use App\Services\FraudControlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

/**
 * Создание автозапчасти с fraud check, транзакцией и audit-логом.
 * Production 2026.
 */
final class CreateAutoPart extends CreateRecord
{
    protected static string $resource = AutoPartResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;

        // Fraud check перед созданием
        $fraudCheck = FraudControlService::check([
            'operation' => 'create_auto_part',
            'tenant_id' => filament()->getTenant()->id,
            'user_id' => auth()->id(),
            'data' => $data,
        ]);

        if (!$fraudCheck['allowed']) {
            $this->notification->make()
                ->title('Подозрение на мошенничество')
                ->body($fraudCheck['reason'] ?? 'Операция заблокирована')
                ->danger()
                ->send();

            $this->halt();
        }

        Log::channel('audit')->info('Creating auto part', [
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
            // Событие создания запчасти
            event(new AutoPartCreated(
                $this->record,
                $this->record->correlation_id
            ));

            Log::channel('audit')->info('Auto part created successfully', [
                'correlation_id' => $this->record->correlation_id,
                'part_id' => $this->record->id,
                'sku' => $this->record->sku,
                'tenant_id' => filament()->getTenant()->id,
            ]);

            $this->notification->make()
                ->title('Запчасть создана')
                ->body("SKU: {$this->record->sku}, остаток: {$this->record->current_stock} шт")
                ->success()
                ->send();
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
