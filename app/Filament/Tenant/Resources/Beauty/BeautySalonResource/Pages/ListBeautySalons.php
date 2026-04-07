<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListBeautySalons — список салонов красоты.
 *
 * Filament Tenant Panel page.
 * Tenant-scoped: все данные фильтруются через BeautySalonResource::getEloquentQuery().
 *
 * CANON 2026: no facades, no constructor injection on Livewire Pages.
 *
 * @package CatVRF\Filament\Tenant
 * @version 2026.1
 */
final class ListBeautySalons extends ListRecords
{
    protected static string $resource = BeautySalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить салон'),
        ];
    }
}
