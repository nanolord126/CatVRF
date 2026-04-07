<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListBeautySalons — общий список салонов (Pages namespace).
 *
 * Используется BeautySalonResource из каталога Pages/.
 * Tenant-scoped через BeautySalonResource::getEloquentQuery().
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
