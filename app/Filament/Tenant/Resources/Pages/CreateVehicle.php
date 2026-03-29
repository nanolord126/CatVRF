<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Vehicle\Pages;

use use App\Filament\Tenant\Resources\VehicleResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    public function getTitle(): string
    {
        return 'Create Vehicle';
    }
}