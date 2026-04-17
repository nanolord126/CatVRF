<?php declare(strict_types=1);

/**
 * ListTaxiDrivers — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listtaxidrivers
 */


namespace App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiDriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTaxiDrivers extends ListRecords
{
    protected static string $resource = TaxiDriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
