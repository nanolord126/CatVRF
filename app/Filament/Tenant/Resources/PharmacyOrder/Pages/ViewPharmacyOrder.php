<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PharmacyOrder\Pages;
use App\Filament\Tenant\Resources\PharmacyOrderResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordPharmacyOrder extends ViewRecord {
    protected static string $resource = PharmacyOrderResource::class;
}
