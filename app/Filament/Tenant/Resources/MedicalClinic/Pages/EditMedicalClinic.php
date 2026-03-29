<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalClinic\Pages;
use App\Filament\Tenant\Resources\MedicalClinicResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordMedicalClinic extends EditRecord {
    protected static string $resource = MedicalClinicResource::class;
}
