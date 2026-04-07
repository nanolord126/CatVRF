<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\SalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\SalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListSalons extends ListRecords
{
    protected static string $resource = SalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Создать салон'),
        ];
    }
}
