<?php

namespace App\Filament\Tenant\Resources\GeoZoneResource\Pages;

use App\Filament\Tenant\Resources\GeoZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeoZone extends EditRecord
{
    protected static string $resource = GeoZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
