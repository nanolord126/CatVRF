<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PetServices\Pages;
use App\Filament\Tenant\Resources\PetServicesResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsPetServices extends ListRecords {
    protected static string $resource = PetServicesResource::class;
}
