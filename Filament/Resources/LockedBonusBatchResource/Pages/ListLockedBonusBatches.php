<?php

declare(strict_types=1);

namespace Filament\Resources\LockedBonusBatchResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListLockedBonusBatches extends ListRecords
{
    protected static string $resource = \Filament\Resources\LockedBonusBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
