<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Appointment\Pages;
use App\Filament\Tenant\Resources\AppointmentResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsAppointment extends ListRecords {
    protected static string $resource = AppointmentResource::class;
}
