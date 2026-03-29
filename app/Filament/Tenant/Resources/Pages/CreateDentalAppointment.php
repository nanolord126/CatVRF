<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalAppointment\Pages;

use use App\Filament\Tenant\Resources\DentalAppointmentResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalAppointment extends CreateRecord
{
    protected static string $resource = DentalAppointmentResource::class;

    public function getTitle(): string
    {
        return 'Create DentalAppointment';
    }
}