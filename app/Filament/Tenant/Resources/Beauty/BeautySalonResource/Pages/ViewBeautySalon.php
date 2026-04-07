<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * ViewBeautySalon — просмотр салона красоты.
 *
 * Filament Tenant Panel page.
 * Tenant-scoped: данные фильтруются через BeautySalonResource::getEloquentQuery().
 *
 * CANON 2026: no facades, no stubs.
 *
 * @package CatVRF\Filament\Tenant
 * @version 2026.1
 */
final class ViewBeautySalon extends ViewRecord
{
    protected static string $resource = BeautySalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
