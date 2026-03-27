<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageItemResource\Pages;

use App\Filament\Tenant\Resources\BeverageItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateBeverageItem extends CreateRecord
{
    protected static string $resource = BeverageItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = request()->header('X-Correlation-ID', (string) Str::uuid());

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('New Drink Item Added to Catalog', [
            'item_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
