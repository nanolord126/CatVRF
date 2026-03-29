<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Vehicle\Pages;
use App\Filament\Tenant\Resources\VehicleResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordVehicle extends CreateRecord {
    protected static string $resource = VehicleResource::class;
}
