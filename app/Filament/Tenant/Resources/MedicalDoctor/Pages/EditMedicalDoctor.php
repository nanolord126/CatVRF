<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalDoctor\Pages;
use App\Filament\Tenant\Resources\MedicalDoctorResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordMedicalDoctor extends EditRecord {
    protected static string $resource = MedicalDoctorResource::class;
}
