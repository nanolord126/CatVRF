<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FurnitureProduct\Pages;
use App\Filament\Tenant\Resources\FurnitureProductResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordFurnitureProduct extends CreateRecord {
    protected static string $resource = FurnitureProductResource::class;
}
