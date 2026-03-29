<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalDoctor\Pages;
use App\Filament\Tenant\Resources\MedicalDoctorResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordMedicalDoctor extends ViewRecord {
    protected static string $resource = MedicalDoctorResource::class;
}
