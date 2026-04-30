<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\ReturnResource\Pages;

use App\Domains\Supermarket\Filament\Resources\ReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListReturns - Страница списка возвратов.
 */
class ListReturns extends ListRecords
{
    protected static string $resource = ReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
