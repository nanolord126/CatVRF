<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Vehicle\Pages;

use use App\Filament\Tenant\Resources\VehicleResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewVehicle extends ViewRecord
{
    protected static string $resource = VehicleResource::class;

    public function getTitle(): string
    {
        return 'View Vehicle';
    }
}