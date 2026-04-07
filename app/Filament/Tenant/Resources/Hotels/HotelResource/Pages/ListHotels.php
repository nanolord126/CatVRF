<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\HotelResource\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListHotels — страница списка отелей для HotelResource.
 *
 * Filament v3 Page: tenant-scoped через Resource::getEloquentQuery().
 *
 * @package App\Filament\Tenant\Resources\Hotels\HotelResource\Pages
 */
final class ListHotels extends ListRecords
{
    protected static string $resource = HotelResource::class;

    /**
     * Действия в заголовке страницы.
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
