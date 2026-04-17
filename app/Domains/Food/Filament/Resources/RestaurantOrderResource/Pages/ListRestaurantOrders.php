<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources\RestaurantOrderResource\Pages;

use App\Domains\Food\Filament\Resources\RestaurantOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListRestaurantOrders extends ListRecords
{
    protected static string $resource = RestaurantOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
