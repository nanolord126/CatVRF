<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Appointment\Pages;
use App\Filament\Tenant\Resources\AppointmentResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordAppointment extends ViewRecord {
    protected static string $resource = AppointmentResource::class;
}
