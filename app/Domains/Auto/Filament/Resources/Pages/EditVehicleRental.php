<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\Pages;

use App\Domains\Auto\Filament\Resources\VehicleRentalResource;
use Filament\Resources\Pages\EditRecord;

final class EditVehicleRental extends EditRecord
{
    protected static string $resource = VehicleRentalResource::class;
}
