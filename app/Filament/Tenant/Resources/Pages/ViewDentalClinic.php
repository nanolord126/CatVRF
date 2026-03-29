<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinic\Pages;

use use App\Filament\Tenant\Resources\DentalClinicResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewDentalClinic extends ViewRecord
{
    protected static string $resource = DentalClinicResource::class;

    public function getTitle(): string
    {
        return 'View DentalClinic';
    }
}