<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\Medical\AppointmentResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;
}
