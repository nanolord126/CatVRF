<?php

namespace App\Filament\Tenant\Resources\B2B\SupplierResource\Pages;

use App\Filament\Tenant\Resources\B2B\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
