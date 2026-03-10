<?php

namespace App\Filament\Tenant\Resources\Marketplace\TaxiTripResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\TaxiTripResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxiTrip extends EditRecord
{
    protected static string $resource = TaxiTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
