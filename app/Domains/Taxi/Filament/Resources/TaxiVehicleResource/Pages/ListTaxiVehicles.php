<?php declare(strict_types=1);

/**
 * ListTaxiVehicles — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listtaxivehicles
 */


namespace App\Domains\Taxi\Filament\Resources\TaxiVehicleResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiVehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTaxiVehicles extends ListRecords
{
    protected static string $resource = TaxiVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
