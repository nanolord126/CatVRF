<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PetServices\Pages;

use use App\Filament\Tenant\Resources\PetServicesResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreatePetServices extends CreateRecord
{
    protected static string $resource = PetServicesResource::class;

    public function getTitle(): string
    {
        return 'Create PetServices';
    }
}