<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DentalAppointment\Pages;
use App\Filament\Tenant\Resources\DentalAppointmentResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsDentalAppointment extends ListRecords {
    protected static string $resource = DentalAppointmentResource::class;
}
