<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalSupplies\Pages;
use App\Filament\Tenant\Resources\MedicalSuppliesResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordMedicalSupplies extends EditRecord {
    protected static string $resource = MedicalSuppliesResource::class;
}
