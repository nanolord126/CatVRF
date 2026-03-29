<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\ElectronicsProduct\Pages;
use App\Filament\Tenant\Resources\ElectronicsProductResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordElectronicsProduct extends EditRecord {
    protected static string $resource = ElectronicsProductResource::class;
}
