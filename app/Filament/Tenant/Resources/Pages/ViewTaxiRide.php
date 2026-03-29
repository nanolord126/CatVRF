<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TaxiRide\Pages;

use use App\Filament\Tenant\Resources\TaxiRideResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewTaxiRide extends ViewRecord
{
    protected static string $resource = TaxiRideResource::class;

    public function getTitle(): string
    {
        return 'View TaxiRide';
    }
}