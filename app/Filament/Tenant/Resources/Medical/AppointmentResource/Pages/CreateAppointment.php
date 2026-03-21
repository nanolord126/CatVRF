<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\Medical\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
}
