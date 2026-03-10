<?php

namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;

use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
