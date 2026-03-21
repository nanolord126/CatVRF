<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages;

use App\Filament\Tenant\Resources\Auto\TaxiRideResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewTaxiRide extends ViewRecord
{
    protected static string $resource = TaxiRideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
