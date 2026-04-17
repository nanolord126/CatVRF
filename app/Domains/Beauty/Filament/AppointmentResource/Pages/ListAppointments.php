<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\AppointmentResource\Pages;

use App\Domains\Beauty\Filament\AppointmentResource;
use Filament\Resources\Pages\ListRecords;

final class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;
}
