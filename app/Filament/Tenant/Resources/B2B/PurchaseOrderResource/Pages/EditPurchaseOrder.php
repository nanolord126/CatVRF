<?php

namespace App\Filament\Tenant\Resources\B2B\PurchaseOrderResource\Pages;

use App\Filament\Tenant\Resources\B2B\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
