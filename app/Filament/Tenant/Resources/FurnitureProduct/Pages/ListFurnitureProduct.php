<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FurnitureProduct\Pages;
use App\Filament\Tenant\Resources\FurnitureProductResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFurnitureProduct extends ListRecords {
    protected static string $resource = FurnitureProductResource::class;
}
