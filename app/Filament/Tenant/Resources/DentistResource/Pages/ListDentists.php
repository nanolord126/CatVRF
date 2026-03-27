<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentistResource\Pages;

use App\Filament\Tenant\Resources\DentistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListDentists extends ListRecords
{
    protected static string $resource = DentistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
