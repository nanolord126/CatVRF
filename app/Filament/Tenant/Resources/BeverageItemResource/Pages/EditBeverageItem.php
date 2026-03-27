<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageItemResource\Pages;

use App\Filament\Tenant\Resources\BeverageItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditBeverageItem extends EditRecord
{
    protected static string $resource = BeverageItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = request()->header('X-Correlation-ID', (string) Str::uuid());
        return $data;
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Beverage Catalog Item Modified', [
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
