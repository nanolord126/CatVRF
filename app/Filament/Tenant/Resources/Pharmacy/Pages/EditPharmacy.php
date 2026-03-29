<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Pharmacy\Pages;
use App\Filament\Tenant\Resources\PharmacyResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordPharmacy extends EditRecord {
    protected static string $resource = PharmacyResource::class;
}
