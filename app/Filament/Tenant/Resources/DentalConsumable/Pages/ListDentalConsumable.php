<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DentalConsumable\Pages;
use App\Filament\Tenant\Resources\DentalConsumableResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsDentalConsumable extends ListRecords {
    protected static string $resource = DentalConsumableResource::class;
}
