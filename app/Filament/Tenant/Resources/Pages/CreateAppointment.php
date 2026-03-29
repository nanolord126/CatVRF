<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Appointment\Pages;

use use App\Filament\Tenant\Resources\AppointmentResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return 'Create Appointment';
    }
}