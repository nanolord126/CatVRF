<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PharmacyOrder\Pages;
use App\Filament\Tenant\Resources\PharmacyOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordPharmacyOrder extends EditRecord {
    protected static string $resource = PharmacyOrderResource::class;
}
