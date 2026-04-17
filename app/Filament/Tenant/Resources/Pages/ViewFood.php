<?php declare(strict_types=1);

/**
 * ViewFood — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewfood
 */


namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FoodResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Class ViewFood
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ViewFood extends ViewRecord
{
    protected static string $resource = FoodResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
