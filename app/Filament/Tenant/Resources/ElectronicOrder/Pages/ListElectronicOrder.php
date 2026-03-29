<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\ElectronicOrder\Pages;
use App\Filament\Tenant\Resources\ElectronicOrderResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsElectronicOrder extends ListRecords {
    protected static string $resource = ElectronicOrderResource::class;
}
