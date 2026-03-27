<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageSubscriptionResource\Pages;

use App\Filament\Tenant\Resources\BeverageSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateBeverageSubscription extends CreateRecord
{
    protected static string $resource = BeverageSubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = (string) Str::uuid();
        $data['starts_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Beverage Subscription Manual Grant', [
            'subscription_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }
}
