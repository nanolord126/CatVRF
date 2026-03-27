<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageOrderResource\Pages;

use App\Filament\Tenant\Resources\BeverageOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBeverageOrders extends ListRecords
{
    protected static string $resource = BeverageOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Manually Enter Order')
                ->icon('heroicon-o-keyboard'),
        ];
    }
}
