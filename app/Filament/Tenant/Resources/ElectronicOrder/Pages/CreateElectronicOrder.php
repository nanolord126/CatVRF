<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\ElectronicOrder\Pages;
use App\Filament\Tenant\Resources\ElectronicOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordElectronicOrder extends CreateRecord {
    protected static string $resource = ElectronicOrderResource::class;
}
