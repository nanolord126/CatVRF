<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalAppointment\Pages;
use App\Filament\Tenant\Resources\MedicalAppointmentResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsMedicalAppointment extends ListRecords {
    protected static string $resource = MedicalAppointmentResource::class;
}
