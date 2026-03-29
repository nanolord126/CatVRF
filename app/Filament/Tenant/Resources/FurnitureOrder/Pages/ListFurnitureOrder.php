<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FurnitureOrder\Pages;
use App\Filament\Tenant\Resources\FurnitureOrderResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFurnitureOrder extends ListRecords {
    protected static string $resource = FurnitureOrderResource::class;
}
