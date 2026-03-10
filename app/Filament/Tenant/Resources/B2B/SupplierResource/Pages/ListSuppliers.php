<?php

namespace App\Filament\Tenant\Resources\B2B\SupplierResource\Pages;

use App\Filament\Tenant\Resources\B2B\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
