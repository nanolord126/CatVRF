<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiRideResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiRideResource;
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
