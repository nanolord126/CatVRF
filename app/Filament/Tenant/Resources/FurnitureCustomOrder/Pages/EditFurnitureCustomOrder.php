<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FurnitureCustomOrder\Pages;
use App\Filament\Tenant\Resources\FurnitureCustomOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordFurnitureCustomOrder extends EditRecord {
    protected static string $resource = FurnitureCustomOrderResource::class;
}
