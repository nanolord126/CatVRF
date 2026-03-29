<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StationeryProduct\Pages;
use App\Filament\Tenant\Resources\StationeryProductResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordStationeryProduct extends EditRecord {
    protected static string $resource = StationeryProductResource::class;
}
