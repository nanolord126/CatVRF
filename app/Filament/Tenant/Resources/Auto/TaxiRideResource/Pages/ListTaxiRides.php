<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages;

use App\Filament\Tenant\Resources\Auto\TaxiRideResource;
use Filament\Actions;
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
