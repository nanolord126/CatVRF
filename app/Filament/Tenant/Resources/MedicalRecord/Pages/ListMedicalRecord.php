<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalRecord\Pages;
use App\Filament\Tenant\Resources\MedicalRecordResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsMedicalRecord extends ListRecords {
    protected static string $resource = MedicalRecordResource::class;
}
