<?php

namespace Modules\Bonuses\Interfaces\Filament\Resources\BonusResource\Pages;

use Modules\Bonuses\Interfaces\Filament\Resources\BonusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBonus extends EditRecord
{
    protected static string $resource = BonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
