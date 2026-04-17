<?php declare(strict_types=1);

namespace Modules\Taxi\Filament\TaxiRideResource\Pages;

use Modules\Taxi\Filament\TaxiRideResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewTaxiRide extends ViewRecord
{
    protected static string $resource = TaxiRideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
