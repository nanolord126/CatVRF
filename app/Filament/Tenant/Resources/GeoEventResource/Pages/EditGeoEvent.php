<?php

namespace App\Filament\Tenant\Resources\GeoEventResource\Pages;

use App\Filament\Tenant\Resources\GeoEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeoEvent extends EditRecord
{
    protected static string $resource = GeoEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
