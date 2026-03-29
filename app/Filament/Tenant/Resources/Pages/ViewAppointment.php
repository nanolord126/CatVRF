<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Appointment\Pages;

use use App\Filament\Tenant\Resources\AppointmentResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return 'View Appointment';
    }
}