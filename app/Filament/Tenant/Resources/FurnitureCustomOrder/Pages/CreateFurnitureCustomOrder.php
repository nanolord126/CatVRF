<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FurnitureCustomOrder\Pages;
use App\Filament\Tenant\Resources\FurnitureCustomOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordFurnitureCustomOrder extends CreateRecord {
    protected static string $resource = FurnitureCustomOrderResource::class;
}
