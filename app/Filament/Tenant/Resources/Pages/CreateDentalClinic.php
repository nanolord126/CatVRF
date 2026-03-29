<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinic\Pages;

use use App\Filament\Tenant\Resources\DentalClinicResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalClinic extends CreateRecord
{
    protected static string $resource = DentalClinicResource::class;

    public function getTitle(): string
    {
        return 'Create DentalClinic';
    }
}