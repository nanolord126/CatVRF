<?php

namespace Modules\Bonuses\Interfaces\Filament\Resources\BonusResource\Pages;

use Modules\Bonuses\Interfaces\Filament\Resources\BonusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBonuses extends ListRecords
{
    protected static string $resource = BonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
