<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\AppointmentResource\Pages;

use App\Domains\Beauty\Filament\AppointmentResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;
}
