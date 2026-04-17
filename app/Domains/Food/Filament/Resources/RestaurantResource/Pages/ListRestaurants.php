<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources\RestaurantResource\Pages;

use App\Domains\Food\Filament\Resources\RestaurantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListRestaurants extends ListRecords
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
