<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiVehicleResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiVehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTaxiVehicles extends ListRecords
{
    protected static string $resource = TaxiVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
