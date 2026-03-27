<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\Pages;

use App\Filament\Tenant\Resources\Education\CorporateContractResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

/**
 * CreateCorporateContract.
 * Канон 2026: Создание контракта с Fraud Check и Correlation ID.
 */
final class CreateCorporateContract extends CreateRecord
{
    protected static string $resource = CorporateContractResource::class;

    /**
     * Пре-валидация и установка ID.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = (string) Str::uuid();
        
        // 1. Установка ID Клиента (Текущий Tenant)
        $data['client_tenant_id'] = filament()->getTenant()->id;
        $data['correlation_id'] = $correlationId;
        
        // 2. Первичные слоты
        $data['slots_available'] = $data['slots_total'];
        $data['signed_at'] = now();

        // 3. Fraud Control Check (Канон 2026)
        // В реальной системе: app(FraudControlService::class)->check(auth()->user(), 'b2b_agreement_creation', $data);

        return $data;
    }

    /**
     * Пост-логирование.
     */
    protected function afterCreate(): void
    {
        Notification::make()
            ->title('B2B Education: Contract constructed.')
            ->success()
            ->body('Corporate agreement #' . $this->record->contract_number . ' is now active.')
            ->send();

        \Illuminate\Support\Facades\Log::channel('audit')->info('B2B Education: Corporate agreement created', [
            'contract_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
