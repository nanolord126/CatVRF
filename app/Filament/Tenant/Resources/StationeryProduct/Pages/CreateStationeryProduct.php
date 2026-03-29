<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StationeryProduct\Pages;
use App\Filament\Tenant\Resources\StationeryProductResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordStationeryProduct extends CreateRecord {
    protected static string $resource = StationeryProductResource::class;
}
