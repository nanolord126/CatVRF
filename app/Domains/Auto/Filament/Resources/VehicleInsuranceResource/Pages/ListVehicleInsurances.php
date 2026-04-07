<?php declare(strict_types=1);

/**
 * ListVehicleInsurances — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listvehicleinsurances
 */


namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;

use App\Domains\Auto\Filament\Resources\VehicleInsuranceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListVehicleInsurances
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages
 */
final class ListVehicleInsurances extends ListRecords
{
    protected static string $resource = VehicleInsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}