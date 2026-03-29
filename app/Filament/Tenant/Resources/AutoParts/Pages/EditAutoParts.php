<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\AutoParts\Pages;
use App\Filament\Tenant\Resources\AutoPartsResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordAutoParts extends EditRecord {
    protected static string $resource = AutoPartsResource::class;
}
