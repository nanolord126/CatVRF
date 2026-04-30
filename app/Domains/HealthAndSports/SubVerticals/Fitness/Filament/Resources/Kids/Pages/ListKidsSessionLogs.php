<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsSessionLogResource;
use Filament\Resources\Pages\ListRecords;

final class ListKidsSessionLogs extends ListRecords
{
    protected static string $resource = KidsSessionLogResource::class;
}
