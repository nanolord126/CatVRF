<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupermarketOrderResource\Pages;

use App\Filament\Resources\SupermarketOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListSupermarketOrders extends ListRecords
{
    protected static string $resource = SupermarketOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
