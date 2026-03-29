<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StationeryStore\Pages;
use App\Filament\Tenant\Resources\StationeryStoreResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordStationeryStore extends EditRecord {
    protected static string $resource = StationeryStoreResource::class;
}
