<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CarRental\Pages;

use App\Filament\Tenant\Resources\CarRental\CarResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

final class ListCars extends ListRecords
{
    protected static string $resource = CarResource::class;

    /**
     * Actions: Comprehensive Vehicle Creation.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Fleet Member')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
