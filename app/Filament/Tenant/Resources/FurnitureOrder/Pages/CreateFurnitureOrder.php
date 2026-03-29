<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FurnitureOrder\Pages;
use App\Filament\Tenant\Resources\FurnitureOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordFurnitureOrder extends CreateRecord {
    protected static string $resource = FurnitureOrderResource::class;
}
