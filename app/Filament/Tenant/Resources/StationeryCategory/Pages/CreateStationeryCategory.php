<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StationeryCategory\Pages;
use App\Filament\Tenant\Resources\StationeryCategoryResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordStationeryCategory extends CreateRecord {
    protected static string $resource = StationeryCategoryResource::class;
}
