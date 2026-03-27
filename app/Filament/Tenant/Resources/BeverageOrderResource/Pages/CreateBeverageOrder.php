<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageOrderResource\Pages;

use App\Filament\Tenant\Resources\BeverageOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateBeverageOrder extends CreateRecord
{
    protected static string $resource = BeverageOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = (string) Str::uuid();
        $data['idempotency_key'] = (string) Str::uuid();
        $data['ml_fraud_score'] = 0.0;

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Manual Beverage Order Injected', [
            'order_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }
}
