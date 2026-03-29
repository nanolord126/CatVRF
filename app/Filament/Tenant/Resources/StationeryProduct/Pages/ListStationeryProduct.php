<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StationeryProduct\Pages;
use App\Filament\Tenant\Resources\StationeryProductResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsStationeryProduct extends ListRecords {
    protected static string $resource = StationeryProductResource::class;
}
