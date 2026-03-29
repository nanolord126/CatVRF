<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\JewelryProduct\Pages;
use App\Filament\Tenant\Resources\JewelryProductResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsJewelryProduct extends ListRecords {
    protected static string $resource = JewelryProductResource::class;
}
