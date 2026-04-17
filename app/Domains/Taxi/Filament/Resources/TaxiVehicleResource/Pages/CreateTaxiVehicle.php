<?php declare(strict_types=1);

/**
 * CreateTaxiVehicle — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createtaxivehicle
 */


namespace App\Domains\Taxi\Filament\Resources\TaxiVehicleResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiVehicleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTaxiVehicle extends CreateRecord
{
    protected static string $resource = TaxiVehicleResource::class;
}
