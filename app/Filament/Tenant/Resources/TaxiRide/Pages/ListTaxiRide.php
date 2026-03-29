<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\TaxiRide\Pages;
use App\Filament\Tenant\Resources\TaxiRideResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsTaxiRide extends ListRecords {
    protected static string $resource = TaxiRideResource::class;
}
