<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

final class ListMasters extends ListRecords
{
    protected static string $resource = MasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Выполнить наём мастера'),
        ];
    }
}
