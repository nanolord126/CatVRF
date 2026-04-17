<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiRideResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiRideResource;
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
