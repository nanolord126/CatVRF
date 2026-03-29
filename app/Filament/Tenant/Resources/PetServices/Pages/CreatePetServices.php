<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PetServices\Pages;
use App\Filament\Tenant\Resources\PetServicesResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordPetServices extends CreateRecord {
    protected static string $resource = PetServicesResource::class;
}
