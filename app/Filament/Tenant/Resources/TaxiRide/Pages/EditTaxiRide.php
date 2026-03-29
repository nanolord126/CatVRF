<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\TaxiRide\Pages;
use App\Filament\Tenant\Resources\TaxiRideResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordTaxiRide extends EditRecord {
    protected static string $resource = TaxiRideResource::class;
}
