<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages;

use App\Filament\Tenant\Resources\Auto\TaxiRideResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateTaxiRide extends CreateRecord
{
    protected static string $resource = TaxiRideResource::class;
}
