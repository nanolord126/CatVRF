<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageItemResource\Pages;

use App\Filament\Tenant\Resources\BeverageItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBeverageItems extends ListRecords
{
    protected static string $resource = BeverageItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Introduce Drink Item')
                ->icon('heroicon-o-sparkles'),
        ];
    }
}
