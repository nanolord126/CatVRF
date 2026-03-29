<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PetServices\Pages;

use use App\Filament\Tenant\Resources\PetServicesResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewPetServices extends ViewRecord
{
    protected static string $resource = PetServicesResource::class;

    public function getTitle(): string
    {
        return 'View PetServices';
    }
}