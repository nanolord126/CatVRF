<?php

declare(strict_types=1);

namespace Modules\Bonuses\Interfaces\Filament\Resources\BonusResource\Pages;

use Modules\Bonuses\Interfaces\Filament\Resources\BonusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBonus extends CreateRecord
{
    protected static string $resource = BonusResource::class;
}
