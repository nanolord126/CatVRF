<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
}
