<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotel\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

/**
 * ListHotel — страница списка отелей (Hotel namespace).
 *
 * Filament v3 Page: tenant-scoped через Resource::getEloquentQuery().
 */
final class ListHotel extends ListRecords
{
    protected static string $resource = HotelResource::class;

    /**
     * Действия в заголовке.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новая запись')
                ->icon('heroicon-m-plus'),
        ];
    }
}
