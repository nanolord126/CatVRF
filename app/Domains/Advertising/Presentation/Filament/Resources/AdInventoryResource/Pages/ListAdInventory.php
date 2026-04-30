<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AdInventoryResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdInventoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAdInventory extends ListRecords
{
    protected static string $resource = AdInventoryResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
