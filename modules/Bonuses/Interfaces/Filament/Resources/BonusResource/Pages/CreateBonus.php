<?php

namespace Modules\Bonuses\Interfaces\Filament\Resources\BonusResource\Pages;

use Modules\Bonuses\Interfaces\Filament\Resources\BonusResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBonus extends CreateRecord
{
    protected static string $resource = BonusResource::class;
}
