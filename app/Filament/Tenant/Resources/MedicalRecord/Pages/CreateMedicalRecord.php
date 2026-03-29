<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalRecord\Pages;
use App\Filament\Tenant\Resources\MedicalRecordResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordMedicalRecord extends CreateRecord {
    protected static string $resource = MedicalRecordResource::class;
}
