<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageSubscriptionResource\Pages;

use App\Filament\Tenant\Resources\BeverageSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

final class EditBeverageSubscription extends EditRecord
{
    protected static string $resource = BeverageSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Beverage Subscription Configuration Updated', [
            'subscription_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }
}
