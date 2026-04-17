<?php declare(strict_types=1);

namespace Modules\Taxi\Filament\TaxiRideResource\Pages;

use Modules\Taxi\Filament\TaxiRideResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTaxiRides extends ListRecords
{
    protected static string $resource = TaxiRideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
