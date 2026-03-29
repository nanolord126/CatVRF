<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalAppointment\Pages;
use App\Filament\Tenant\Resources\MedicalAppointmentResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordMedicalAppointment extends EditRecord {
    protected static string $resource = MedicalAppointmentResource::class;
}
