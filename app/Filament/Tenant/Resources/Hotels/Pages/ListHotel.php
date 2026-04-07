<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * ListHotel — страница списка отелей.
 *
 * Filament v3 Page: tenant-scoped через Resource::getEloquentQuery().
 * Без constructor injection — сервисы получаются через app().
 *
 * @package App\Filament\Tenant\Resources\Hotels\Pages
 */
final class ListHotel extends ListRecords
{
    protected static string $resource = HotelsResource::class;

    /**
     * Действия в заголовке страницы списка.
     *
     * @return array<\Filament\Actions\Action>
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
