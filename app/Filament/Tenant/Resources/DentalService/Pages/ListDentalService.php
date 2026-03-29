<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DentalService\Pages;
use App\Filament\Tenant\Resources\DentalServiceResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsDentalService extends ListRecords {
    protected static string $resource = DentalServiceResource::class;
}
