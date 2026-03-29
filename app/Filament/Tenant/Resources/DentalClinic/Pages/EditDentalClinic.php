<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DentalClinic\Pages;
use App\Filament\Tenant\Resources\DentalClinicResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordDentalClinic extends EditRecord {
    protected static string $resource = DentalClinicResource::class;
}
