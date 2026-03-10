<?php

namespace App\Filament\Tenant\Resources\DeliveryZoneResource\Pages;

use App\Filament\Tenant\Resources\DeliveryZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDeliveryZones extends ManageRecords
{
    protected static string $resource = DeliveryZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
