<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalHealthcare\Pages;
use App\Filament\Tenant\Resources\MedicalHealthcareResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordMedicalHealthcare extends EditRecord {
    protected static string $resource = MedicalHealthcareResource::class;
}
