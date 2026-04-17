<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\AppointmentResource\Pages;

use App\Domains\Beauty\Filament\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
}
