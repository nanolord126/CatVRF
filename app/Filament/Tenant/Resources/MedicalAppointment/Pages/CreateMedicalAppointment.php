<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalAppointment\Pages;
use App\Filament\Tenant\Resources\MedicalAppointmentResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordMedicalAppointment extends CreateRecord {
    protected static string $resource = MedicalAppointmentResource::class;
}
