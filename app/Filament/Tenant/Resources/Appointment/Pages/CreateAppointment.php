<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Appointment\Pages;
use App\Filament\Tenant\Resources\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordAppointment extends CreateRecord {
    protected static string $resource = AppointmentResource::class;
}
