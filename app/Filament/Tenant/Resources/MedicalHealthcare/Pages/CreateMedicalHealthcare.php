<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalHealthcare\Pages;
use App\Filament\Tenant\Resources\MedicalHealthcareResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordMedicalHealthcare extends CreateRecord {
    protected static string $resource = MedicalHealthcareResource::class;
}
