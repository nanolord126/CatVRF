<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\GardenProduct\Pages;
use App\Filament\Tenant\Resources\GardenProductResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsGardenProduct extends ListRecords {
    protected static string $resource = GardenProductResource::class;
}
