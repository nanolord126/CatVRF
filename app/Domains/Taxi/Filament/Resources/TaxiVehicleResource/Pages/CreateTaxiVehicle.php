<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiVehicleResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiVehicleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTaxiVehicle extends CreateRecord
{
    protected static string $resource = TaxiVehicleResource::class;
}
