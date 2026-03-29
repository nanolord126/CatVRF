<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalAppointment\Pages;

use use App\Filament\Tenant\Resources\DentalAppointmentResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewDentalAppointment extends ViewRecord
{
    protected static string $resource = DentalAppointmentResource::class;

    public function getTitle(): string
    {
        return 'View DentalAppointment';
    }
}