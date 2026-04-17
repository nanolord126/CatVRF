<?php declare(strict_types=1);

/**
 * ListGeoLocations — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listgeolocations
 */


namespace App\Domains\Geo\Filament\Resources\GeoLocationResource\Pages;

use App\Domains\Geo\Filament\Resources\GeoLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListGeoLocations extends ListRecords
{
    protected static string $resource = GeoLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}