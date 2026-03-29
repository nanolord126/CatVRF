<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Pharmacy\Pages;
use App\Filament\Tenant\Resources\PharmacyResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordPharmacy extends CreateRecord {
    protected static string $resource = PharmacyResource::class;
}
