<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DentalAppointment\Pages;
use App\Filament\Tenant\Resources\DentalAppointmentResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordDentalAppointment extends EditRecord {
    protected static string $resource = DentalAppointmentResource::class;
}
