<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MedicalSupplies\Pages;
use App\Filament\Tenant\Resources\MedicalSuppliesResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordMedicalSupplies extends ViewRecord {
    protected static string $resource = MedicalSuppliesResource::class;
}
