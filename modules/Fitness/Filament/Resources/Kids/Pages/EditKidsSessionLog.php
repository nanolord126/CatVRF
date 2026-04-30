<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsSessionLogResource;
use Filament\Resources\Pages\EditRecord;

final class EditKidsSessionLog extends EditRecord
{
    protected static string $resource = KidsSessionLogResource::class;
}
