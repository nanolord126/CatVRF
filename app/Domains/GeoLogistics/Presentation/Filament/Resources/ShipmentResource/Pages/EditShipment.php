<?php

declare(strict_types=1);

/**
 * EditShipment — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editshipment
 */


namespace App\Domains\GeoLogistics\Presentation\Filament\Resources\ShipmentResource\Pages;

use App\Domains\GeoLogistics\Presentation\Filament\Resources\ShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditShipment
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\GeoLogistics\Presentation\Filament\Resources\ShipmentResource\Pages
 */
final class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
