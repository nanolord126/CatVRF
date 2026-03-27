<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TaxiRideResource\Pages;

use App\Filament\Tenant\Resources\TaxiRideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTaxiRide extends EditRecord
{
    protected static string $resource = TaxiRideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
