<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Filament\Resources\DishResource\Pages;

use App\Domains\Food\Filament\Resources\DishResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListDishes extends ListRecords
{
    protected static string $resource = DishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
