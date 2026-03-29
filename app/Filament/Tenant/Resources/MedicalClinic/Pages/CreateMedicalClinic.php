<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalClinic\Pages;
use App\Filament\Tenant\Resources\MedicalClinicResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordMedicalClinic extends CreateRecord {
    protected static string $resource = MedicalClinicResource::class;
}
